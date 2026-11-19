<?php

namespace App\Services;

use App\Models\InventoryBalance;
use App\Models\ProductMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function getOrCreateBalance(int $productId, string $locationType, int $locationId): InventoryBalance
    {
        return InventoryBalance::firstOrCreate(
            ['product_id' => $productId, 'location_type' => $locationType, 'location_id' => $locationId],
            ['quantity_on_hand' => 0, 'quantity_reserved' => 0]
        );
    }

    public function lockBalance(int $productId, string $locationType, int $locationId): InventoryBalance
    {
        $balance = InventoryBalance::where('product_id', $productId)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        if (!$balance) {
            $balance = InventoryBalance::create([
                'product_id' => $productId,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);
        }

        return $balance;
    }

    public function incrementBalance(int $productId, string $locationType, int $locationId, float $quantity): void
    {
        InventoryBalance::firstOrCreate(
            ['product_id' => $productId, 'location_type' => $locationType, 'location_id' => $locationId],
            ['quantity_on_hand' => 0, 'quantity_reserved' => 0]
        );

        InventoryBalance::where('product_id', $productId)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->increment('quantity_on_hand', $quantity);
    }

    public function decrementBalance(int $productId, string $locationType, int $locationId, float $quantity): void
    {
        InventoryBalance::firstOrCreate(
            ['product_id' => $productId, 'location_type' => $locationType, 'location_id' => $locationId],
            ['quantity_on_hand' => 0, 'quantity_reserved' => 0]
        );

        InventoryBalance::where('product_id', $productId)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->decrement('quantity_on_hand', $quantity);
    }

    public function createMovement(array $data): ProductMovement
    {
        $numberingService = app(NumberingService::class);

        return ProductMovement::create(array_merge(
            ['movement_number' => $numberingService->next('MOVEMENT')],
            $data,
        ));
    }
}
