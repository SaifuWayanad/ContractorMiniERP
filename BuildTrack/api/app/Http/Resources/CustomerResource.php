<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Customer */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customerName' => $this->customer_name,
            'phoneNumber' => $this->phone_number,
            'address' => $this->address,
            'locationId' => $this->location_id,
            'locationName' => $this->location?->location_name,
            'notes' => $this->notes,
            'isArchived' => $this->is_archived,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
