<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use App\Models\ProductMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryBalanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InventoryBalance::with(['product:id,name,code,unit_of_measure'])
            ->when($request->location_type, fn ($q) => $q->where('location_type', $request->location_type))
            ->when($request->location_id, fn ($q) => $q->where('location_id', $request->location_id))
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->category_id, fn ($q) => $q->whereHas('product', fn ($p) => $p->where('category_id', $request->category_id)))
            ->where('quantity_on_hand', '>', 0)
            ->orderByDesc('quantity_on_hand');

        return response()->json($query->paginate($request->per_page ?? 50));
    }

    public function movements(Request $request): JsonResponse
    {
        $query = ProductMovement::with(['product:id,name,code'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->movement_type, fn ($q) => $q->where('movement_type', $request->movement_type))
            ->when($request->from_date, fn ($q) => $q->where('movement_date', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->where('movement_date', '<=', $request->to_date))
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc');

        return response()->json($query->paginate($request->per_page ?? 50));
    }
}
