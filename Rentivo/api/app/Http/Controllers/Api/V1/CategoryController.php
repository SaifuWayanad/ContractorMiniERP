<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::with(['createdBy:id,name'])
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy($request->sort_by ?? 'name', $request->sort_dir ?? 'asc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:categories'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $category = Category::create($validated);

        return response()->json($category->load('createdBy:id,name'), 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($category->load(['createdBy:id,name', 'updatedBy:id,name']));
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', 'unique:categories,code,' . $category->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['updated_by'] = $request->user()->id;
        $category->update($validated);

        return response()->json($category->fresh()->load('updatedBy:id,name'));
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json(['message' => 'Cannot delete category with associated products.'], 422);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
