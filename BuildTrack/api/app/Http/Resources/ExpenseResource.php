<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Expense */
class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'projectId' => $this->project_id,
            'projectName' => $this->project?->project_name ?? '',
            'customerName' => $this->project?->customer?->customer_name ?? '',
            'expenseHeading' => $this->expense_heading,
            'expenseDescription' => $this->expense_description,
            'expenseDate' => $this->expense_date?->toIso8601String(),
            'amount' => Money::of($this->amount),
            'paymentMethod' => $this->payment_method,
            'referenceNumber' => $this->reference_number,
            'notes' => $this->notes,
            'isArchived' => $this->is_archived,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
