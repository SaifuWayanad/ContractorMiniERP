<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\InventoryBalance;
use App\Models\RentalPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispatchService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly NumberingService $numberingService,
    ) {}

    public function post(Dispatch $dispatch, int $postedBy): void
    {
        DB::transaction(function () use ($dispatch, $postedBy) {
            if ($dispatch->status !== 'APPROVED') {
                throw ValidationException::withMessages(['status' => ['Dispatch must be APPROVED before posting.']]);
            }

            if (!in_array($dispatch->project->status, ['ACTIVE'])) {
                throw ValidationException::withMessages(['project' => ['Project must be ACTIVE to dispatch equipment.']]);
            }

            foreach ($dispatch->dispatchDetails as $detail) {
                $warehouseBalance = $this->inventoryService->lockBalance(
                    $detail->product_id,
                    'WAREHOUSE',
                    $dispatch->source_location_id
                );

                if ($warehouseBalance->quantity_on_hand < $detail->quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => ["Insufficient stock for product {$detail->product->code}. Available: {$warehouseBalance->quantity_on_hand}"],
                    ]);
                }

                $this->inventoryService->decrementBalance(
                    $detail->product_id, 'WAREHOUSE', $dispatch->source_location_id, $detail->quantity
                );
                $this->inventoryService->incrementBalance(
                    $detail->product_id, 'PROJECT', $dispatch->project_id, $detail->quantity
                );

                $this->inventoryService->createMovement([
                    'product_id' => $detail->product_id,
                    'movement_type' => 'DISPATCH',
                    'source_type' => Dispatch::class,
                    'source_id' => $dispatch->id,
                    'source_detail_id' => $detail->id,
                    'from_location_type' => 'WAREHOUSE',
                    'from_location_id' => $dispatch->source_location_id,
                    'to_location_type' => 'PROJECT',
                    'to_location_id' => $dispatch->project_id,
                    'quantity' => $detail->quantity,
                    'movement_date' => $dispatch->dispatch_date,
                    'status' => 'POSTED',
                    'created_by' => $postedBy,
                    'posted_by' => $postedBy,
                ]);

                RentalPeriod::create([
                    'source_dispatch_detail_id' => $detail->id,
                    'customer_id' => $dispatch->customer_id,
                    'project_id' => $dispatch->project_id,
                    'product_id' => $detail->product_id,
                    'quantity' => $detail->quantity,
                    'rental_start_date' => $dispatch->dispatch_date,
                    'minimum_rental_days_snapshot' => $detail->minimum_rental_days_snapshot,
                    'minimum_rental_amount_snapshot' => $detail->minimum_rental_amount_snapshot,
                    'addon_amount_per_day_snapshot' => $detail->addon_amount_per_day_snapshot,
                    'tax_rate_snapshot' => $detail->tax_rate_snapshot,
                    'status' => 'OPEN',
                ]);
            }

            $dispatch->update([
                'status' => 'POSTED',
                'posted_by' => $postedBy,
                'posted_at' => now(),
            ]);
        });
    }
}
