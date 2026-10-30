<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Project */
class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'projectName' => $this->project_name,
            'customerId' => $this->customer_id,
            'customerName' => $this->customer?->customer_name ?? '',
            'locationId' => $this->location_id,
            'locationName' => $this->location?->location_name,
            'projectAddress' => $this->project_address,
            'startDate' => $this->start_date?->toIso8601String(),
            'endDate' => $this->end_date?->toIso8601String(),
            'budget' => Money::of($this->budget),
            'description' => $this->description,
            'isArchived' => $this->is_archived,
            'assignedUserId' => $this->assigned_user_id,
            'assignedUserName' => $this->assignedUser?->full_name,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
