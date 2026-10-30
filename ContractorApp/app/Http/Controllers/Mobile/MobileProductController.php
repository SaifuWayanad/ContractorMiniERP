<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::orderBy('name')->get()->map(fn (Product $p) => [
            'id'           => $p->id,
            'name'         => $p->name,
            'unit'         => $p->unit,
            'default_rate' => (float) $p->default_rate,
            'description'  => $p->description,
        ]);

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:150'],
            'unit'         => ['nullable', 'string', 'max:50'],
            'default_rate' => ['nullable', 'numeric', 'min:0'],
            'description'  => ['nullable', 'string', 'max:5000'],
        ]);

        $product = Product::create($data);

        return response()->json(['id' => $product->id, 'name' => $product->name, 'message' => 'Product created.'], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['sometimes', 'string', 'max:150'],
            'unit'         => ['nullable', 'string', 'max:50'],
            'default_rate' => ['nullable', 'numeric', 'min:0'],
            'description'  => ['nullable', 'string', 'max:5000'],
        ]);

        $product->update($data);

        return response()->json(['message' => 'Product updated.']);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }
}
