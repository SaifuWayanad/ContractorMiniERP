<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Purchase */
class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'projectId' => $this->project_id,
            'projectName' => $this->project?->project_name ?? '',
            'customerName' => $this->project?->customer?->customer_name ?? '',
            'productId' => $this->product_id,
            'productName' => $this->product?->product_name ?? '',
            'purchaseDate' => $this->purchase_date?->toIso8601String(),
            'quantity' => Money::of($this->quantity),
            'unitAmount' => Money::of($this->unit_amount),
            'lineTotal' => Money::of($this->line_total),
            'supplier' => $this->supplier,
            'invoiceReference' => $this->invoice_reference,
            'notes' => $this->notes,
            'isArchived' => $this->is_archived,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
