<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\Paginates;
use App\Http\Controllers\Api\Concerns\Scopes;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    use Paginates, Scopes;

    public function index(Request $request): JsonResponse
    {
        $query = Product::orderBy('product_name');

        if (! $this->boolQuery($request, 'includeArchived')) {
            $query->where('is_active', true);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(fn ($q) => $q->where('product_name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        return response()->json($this->paginate($query, $request, ProductResource::class));
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json((new ProductResource($product))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $product = Product::create($this->validated($request) + ['is_active' => true]);

        return response()->json((new ProductResource($product))->resolve(), Response::HTTP_CREATED);
    }

    public function update(Request $request, Product $product): Response
    {
        $data = $request->validate(['isActive' => ['boolean']]);
        $product->update($this->validated($request) + ['is_active' => (bool) ($data['isActive'] ?? true)]);

        return response()->noContent();
    }

    public function destroy(Product $product): Response|JsonResponse
    {
        if (Purchase::where('product_id', $product->id)->where('is_archived', false)->exists()) {
            return response()->json(['message' => 'Cannot delete product that is in use by purchases.'], 409);
        }

        $product->update(['is_active' => false]);

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'productName' => ['required', 'string', 'max:200'],
            'unitOfMeasure' => ['nullable', 'string', 'max:50'],
            'defaultUnitRate' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
        ]);

        return [
            'product_name' => $data['productName'],
            'unit_of_measure' => $data['unitOfMeasure'] ?? '',
            'default_unit_rate' => $data['defaultUnitRate'] ?? null,
            'description' => $data['description'] ?? null,
        ];
    }
}
