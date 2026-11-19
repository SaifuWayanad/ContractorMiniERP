<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TaxRate::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:tax_rates'],
            'name' => ['required', 'string', 'max:255'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after:effective_date'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(TaxRate::create($validated), 201);
    }

    public function show(TaxRate $taxRate): JsonResponse
    {
        return response()->json($taxRate);
    }

    public function update(Request $request, TaxRate $taxRate): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', 'unique:tax_rates,code,' . $taxRate->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'effective_date' => ['sometimes', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $taxRate->update($validated);

        return response()->json($taxRate->fresh());
    }

    public function destroy(TaxRate $taxRate): JsonResponse
    {
        $taxRate->update(['is_active' => false]);

        return response()->json($taxRate->fresh());
    }
}
