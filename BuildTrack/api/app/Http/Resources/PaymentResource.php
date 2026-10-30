<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CustomerPayment */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'projectId' => $this->project_id,
            'projectName' => $this->project?->project_name ?? '',
            'customerId' => $this->project?->customer_id,
            'customerName' => $this->project?->customer?->customer_name ?? '',
            'paymentDate' => $this->payment_date?->toIso8601String(),
            'amountReceived' => Money::of($this->amount_received),
            'paymentMethod' => $this->payment_method,
            'receiptReference' => $this->receipt_reference,
            'notes' => $this->notes,
            'isVoided' => $this->is_voided,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
