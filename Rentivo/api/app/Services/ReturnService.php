<?php

namespace App\Services;

use App\Models\EquipmentReturn;
use App\Models\RentalPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function post(EquipmentReturn $return, int $postedBy): void
    {
        DB::transaction(function () use ($return, $postedBy) {
            if ($return->status !== 'APPROVED') {
                throw ValidationException::withMessages(['status' => ['Return must be APPROVED before posting.']]);
            }

            foreach ($return->returnDetails as $detail) {
                $this->validateReturnQuantity($detail);

                $this->inventoryService->decrementBalance(
                    $detail->product_id, 'PROJECT', $return->project_id, $detail->quantity_returned
                );
                $this->inventoryService->incrementBalance(
                    $detail->product_id, 'WAREHOUSE', $return->warehouse_location_id, $detail->quantity_returned
                );

                $this->inventoryService->createMovement([
                    'product_id' => $detail->product_id,
                    'movement_type' => 'RETURN',
                    'source_type' => EquipmentReturn::class,
                    'source_id' => $return->id,
                    'source_detail_id' => $detail->id,
                    'from_location_type' => 'PROJECT',
                    'from_location_id' => $return->project_id,
                    'to_location_type' => 'WAREHOUSE',
                    'to_location_id' => $return->warehouse_location_id,
                    'quantity' => $detail->quantity_returned,
                    'movement_date' => $return->return_date,
                    'reference_dispatch_detail_id' => $detail->dispatch_detail_id,
                    'status' => 'POSTED',
                    'created_by' => $postedBy,
                    'posted_by' => $postedBy,
                ]);

                $this->closeRentalPeriods($detail, $return->return_date);
            }

            $return->update([
                'status' => 'POSTED',
                'posted_by' => $postedBy,
                'posted_at' => now(),
            ]);
        });
    }

    private function validateReturnQuantity(\App\Models\ReturnDetail $detail): void
    {
        $dispatchDetail = $detail->dispatchDetail;
        $alreadyReturned = \App\Models\ReturnDetail::whereHas(
            'equipmentReturn',
            fn ($q) => $q->where('status', 'POSTED')->where('id', '!=', $detail->return_id)
        )
            ->where('dispatch_detail_id', $detail->dispatch_detail_id)
            ->sum('quantity_returned');

        $alreadyLost = \App\Models\LossDetail::whereHas('loss', fn ($q) => $q->where('status', 'POSTED'))
            ->where('source_dispatch_detail_id', $detail->dispatch_detail_id)
            ->sum('quantity_lost');

        $alreadyTransferred = \App\Models\TransferDetail::whereHas('transfer', fn ($q) => $q->where('status', 'POSTED'))
            ->where('source_dispatch_detail_id', $detail->dispatch_detail_id)
            ->sum('quantity_transferred');

        $available = $dispatchDetail->quantity - $alreadyReturned - $alreadyLost - $alreadyTransferred;

        if ($detail->quantity_returned > $available) {
            throw ValidationException::withMessages([
                'quantity' => ["Return quantity exceeds outstanding quantity. Available: {$available}"],
            ]);
        }
    }

    private function closeRentalPeriods(\App\Models\ReturnDetail $detail, string $returnDate): void
    {
        $remaining = $detail->quantity_returned;

        $openPeriods = RentalPeriod::where('source_dispatch_detail_id', $detail->dispatch_detail_id)
            ->where('status', 'OPEN')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        foreach ($openPeriods as $period) {
            if ($remaining <= 0) {
                break;
            }

            if ($period->quantity <= $remaining) {
                $period->update([
                    'rental_end_date' => $returnDate,
                    'end_reason' => 'RETURN',
                    'status' => 'CLOSED',
                    'closed_at' => now(),
                ]);
                $remaining -= $period->quantity;
            } else {
                // Split: close the returned portion, keep a new period for the rest
                $closedQuantity = $remaining;
                $openQuantity = $period->quantity - $remaining;

                $period->update([
                    'quantity' => $closedQuantity,
                    'rental_end_date' => $returnDate,
                    'end_reason' => 'RETURN',
                    'status' => 'CLOSED',
                    'closed_at' => now(),
                ]);

                RentalPeriod::create([
                    'source_dispatch_detail_id' => $period->source_dispatch_detail_id,
                    'customer_id' => $period->customer_id,
                    'project_id' => $period->project_id,
                    'product_id' => $period->product_id,
                    'quantity' => $openQuantity,
                    'rental_start_date' => $period->rental_start_date,
                    'minimum_rental_days_snapshot' => $period->minimum_rental_days_snapshot,
                    'minimum_rental_amount_snapshot' => $period->minimum_rental_amount_snapshot,
                    'addon_amount_per_day_snapshot' => $period->addon_amount_per_day_snapshot,
                    'tax_rate_snapshot' => $period->tax_rate_snapshot,
                    'parent_rental_period_id' => $period->id,
                    'status' => 'OPEN',
                ]);

                $remaining = 0;
            }
        }
    }
}
