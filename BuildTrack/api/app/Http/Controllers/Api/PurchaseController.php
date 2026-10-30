<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\Paginates;
use App\Http\Controllers\Api\Concerns\Scopes;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PurchaseController extends Controller
{
    use Paginates, Scopes;

    public function index(Request $request): JsonResponse
    {
        $query = Purchase::with(['project.customer', 'product'])->orderByDesc('purchase_date');

        if (! $this->boolQuery($request, 'includeArchived')) {
            $query->where('is_archived', false);
        }

        if ($request->filled('projectId')) {
            $query->where('project_id', (int) $request->query('projectId'));
        }

        if ($request->filled('customerId')) {
            $query->whereHas('project', fn ($p) => $p->where('customer_id', (int) $request->query('customerId')));
        }

        if ($request->filled('productId')) {
            $query->where('product_id', (int) $request->query('productId'));
        }

        if ($request->filled('startDate')) {
            $query->where('purchase_date', '>=', $request->query('startDate'));
        }

        if ($request->filled('endDate')) {
            $query->where('purchase_date', '<=', $request->query('endDate'));
        }

        return response()->json($this->paginate($query, $request, PurchaseResource::class));
    }

    public function show(Purchase $purchase): JsonResponse
    {
        return response()->json((new PurchaseResource($purchase->load(['project.customer', 'product'])))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $purchase = Purchase::create($this->attributes($request));

        return response()->json(['id' => $purchase->id], Response::HTTP_CREATED);
    }

    public function update(Request $request, Purchase $purchase): Response
    {
        $purchase->update($this->attributes($request, withArchived: true));

        return response()->noContent();
    }

    public function destroy(Purchase $purchase): Response
    {
        $purchase->update(['is_archived' => true]);

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function attributes(Request $request, bool $withArchived = false): array
    {
        $rules = [
            'projectId' => ['required', 'integer', 'min:1', 'exists:projects,id'],
            'productId' => ['required', 'integer', 'min:1', 'exists:products,id'],
            'purchaseDate' => ['required', 'date'],
            'quantity' => ['required', 'numeric'],
            'unitAmount' => ['required', 'numeric'],
            'supplier' => ['nullable', 'string', 'max:200'],
            'invoiceReference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
        if ($withArchived) {
            $rules['isArchived'] = ['boolean'];
        }

        $data = $request->validate($rules);

        $attributes = [
            'project_id' => $data['projectId'],
            'product_id' => $data['productId'],
            'purchase_date' => $data['purchaseDate'],
            'quantity' => $data['quantity'],
            'unit_amount' => $data['unitAmount'],
            'line_total' => round($data['quantity'] * $data['unitAmount'], 2),
            'supplier' => $data['supplier'] ?? '',
            'invoice_reference' => $data['invoiceReference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
        if ($withArchived) {
            $attributes['is_archived'] = (bool) ($data['isArchived'] ?? false);
        }

        return $attributes;
    }
}
