<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category:id,name,code'])
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy($request->sort_by ?? 'name', $request->sort_dir ?? 'asc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:50', 'unique:products'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_of_measure' => ['required', 'string', 'max:50'],
            'tracking_method' => ['in:QUANTITY,SERIALIZED'],
            'minimum_rental_days' => ['required', 'numeric', 'min:0'],
            'minimum_rental_amount' => ['required', 'numeric', 'min:0'],
            'addon_amount_per_day' => ['required', 'numeric', 'min:0'],
            'default_tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
            'is_active' => ['boolean'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $product = Product::create($validated);

        return response()->json($product->load('category:id,name'), 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'defaultTaxRate']);

        $product->current_stock = \App\Models\InventoryBalance::where('product_id', $product->id)
            ->get()
            ->groupBy('location_type')
            ->map(fn ($rows) => $rows->sum('quantity_on_hand'));

        return response()->json($product);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'exists:categories,id'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:products,code,' . $product->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_of_measure' => ['sometimes', 'string', 'max:50'],
            'tracking_method' => ['in:QUANTITY,SERIALIZED'],
            'minimum_rental_days' => ['sometimes', 'numeric', 'min:0'],
            'minimum_rental_amount' => ['sometimes', 'numeric', 'min:0'],
            'addon_amount_per_day' => ['sometimes', 'numeric', 'min:0'],
            'default_tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
            'is_active' => ['boolean'],
        ]);

        $validated['updated_by'] = $request->user()->id;
        $product->update($validated);

        return response()->json($product->fresh()->load('category:id,name'));
    }

    public function destroy(Product $product): JsonResponse
    {
        return response()->json(['message' => 'Products cannot be deleted; set is_active to false.'], 422);
    }
}
