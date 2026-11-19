<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Operator::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('phone', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_expiry' => ['nullable', 'date'],
            'status' => ['in:ACTIVE,INACTIVE'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(Operator::create($validated), 201);
    }

    public function show(Operator $operator): JsonResponse
    {
        return response()->json($operator);
    }

    public function update(Request $request, Operator $operator): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_expiry' => ['nullable', 'date'],
            'status' => ['in:ACTIVE,INACTIVE'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $operator->update($validated);

        return response()->json($operator->fresh());
    }

    public function destroy(Operator $operator): JsonResponse
    {
        $operator->update(['is_active' => false]);

        return response()->json($operator->fresh());
    }
}
