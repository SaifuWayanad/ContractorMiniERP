<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $search = $request->string('search')->trim()->value();

        $products = Product::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->orderBy('name')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('products.index', ['products' => $products, 'search' => $search]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.create', ['product' => new Product]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());

        return to_route('products.index')->with('toast', "Product \"{$product->name}\" created.");
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.edit', ['product' => $product]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return to_route('products.index')->with('toast', "Product \"{$product->name}\" updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return to_route('products.index')->with('toast', "Product \"{$product->name}\" deleted.");
    }

    public function quickStore(ProductRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());

        return response()->json([
            'id'   => $product->id,
            'name' => $product->name,
            'unit' => $product->unit ?? '',
            'rate' => (float) ($product->default_rate ?? 0),
        ], 201);
    }
}
