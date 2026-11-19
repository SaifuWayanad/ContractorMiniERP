<?php

namespace App\Services;

use App\Models\Transfer;
use App\Models\RentalPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function post(Transfer $transfer, int $postedBy): void
    {
        DB::transaction(function () use ($transfer, $postedBy) {
            if ($transfer->status !== 'APPROVED') {
                throw ValidationException::withMessages(['status' => ['Transfer must be APPROVED before posting.']]);
            }

            if ($transfer->from_project_id === $transfer->to_project_id) {
                throw ValidationException::withMessages(['project' => ['Source and destination projects cannot be the same.']]);
            }

            foreach ($transfer->transferDetails as $detail) {
                $this->inventoryService->decrementBalance(
                    $detail->product_id, 'PROJECT', $transfer->from_project_id, $detail->quantity_transferred
                );
                $this->inventoryService->incrementBalance(
                    $detail->product_id, 'PROJECT', $transfer->to_project_id, $detail->quantity_transferred
                );

                $this->inventoryService->createMovement([
                    'product_id' => $detail->product_id,
                    'movement_type' => 'TRANSFER_OUT',
                    'source_type' => Transfer::class,
                    'source_id' => $transfer->id,
                    'source_detail_id' => $detail->id,
                    'from_location_type' => 'PROJECT',
                    'from_location_id' => $transfer->from_project_id,
                    'to_location_type' => 'PROJECT',
                    'to_location_id' => $transfer->to_project_id,
                    'quantity' => $detail->quantity_transferred,
                    'movement_date' => $transfer->transfer_date,
                    'reference_dispatch_detail_id' => $detail->source_dispatch_detail_id,
                    'status' => 'POSTED',
                    'created_by' => $postedBy,
                    'posted_by' => $postedBy,
                ]);

                // Close source rental period
                $remaining = $detail->quantity_transferred;
                $openPeriods = RentalPeriod::where('source_dispatch_detail_id', $detail->source_dispatch_detail_id)
                    ->where('project_id', $transfer->from_project_id)
                    ->where('status', 'OPEN')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                foreach ($openPeriods as $period) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $closeQty = min($period->quantity, $remaining);
                    $openQty = $period->quantity - $closeQty;

                    if ($openQty > 0) {
                        // Split: keep an open period for the portion that stays at source
                        $period->update(['quantity' => $closeQty]);
                        RentalPeriod::create([
                            'source_dispatch_detail_id' => $period->source_dispatch_detail_id,
                            'customer_id' => $period->customer_id,
                            'project_id' => $period->project_id,
                            'product_id' => $period->product_id,
                            'quantity' => $openQty,
                            'rental_start_date' => $period->rental_start_date,
                            'minimum_rental_days_snapshot' => $period->minimum_rental_days_snapshot,
                            'minimum_rental_amount_snapshot' => $period->minimum_rental_amount_snapshot,
                            'addon_amount_per_day_snapshot' => $period->addon_amount_per_day_snapshot,
                            'tax_rate_snapshot' => $period->tax_rate_snapshot,
                            'parent_rental_period_id' => $period->id,
                            'status' => 'OPEN',
                        ]);
                    }

                    $period->update([
                        'rental_end_date' => $transfer->transfer_date,
                        'end_reason' => 'TRANSFER',
                        'status' => 'CLOSED',
                        'closed_at' => now(),
                    ]);

                    // Create new rental period at destination
                    RentalPeriod::create([
                        'source_dispatch_detail_id' => $detail->source_dispatch_detail_id,
                        'customer_id' => $transfer->to_customer_id,
                        'project_id' => $transfer->to_project_id,
                        'product_id' => $detail->product_id,
                        'quantity' => $closeQty,
                        'rental_start_date' => $transfer->transfer_date,
                        'minimum_rental_days_snapshot' => $detail->minimum_rental_days_snapshot ?? $period->minimum_rental_days_snapshot,
                        'minimum_rental_amount_snapshot' => $detail->minimum_rental_amount_snapshot ?? $period->minimum_rental_amount_snapshot,
                        'addon_amount_per_day_snapshot' => $detail->addon_amount_per_day_snapshot ?? $period->addon_amount_per_day_snapshot,
                        'tax_rate_snapshot' => $period->tax_rate_snapshot,
                        'parent_rental_period_id' => $period->id,
                        'status' => 'OPEN',
                    ]);

                    $remaining -= $closeQty;
                }
            }

            $transfer->update([
                'status' => 'POSTED',
                'posted_by' => $postedBy,
                'posted_at' => now(),
            ]);
        });
    }
}
