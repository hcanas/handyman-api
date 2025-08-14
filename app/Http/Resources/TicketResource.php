<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority_level' => $this->priority_level,
            'reported_by_id' => $this->reported_by_id,
            'reported_by' => $this->reporter->name,
            'assigned_to_id' => $this->assigned_to_id,
            'assigned_to' => $this->assignee?->name,
            'department_name' => $this->department_name_snapshot,
            'resolved_at' => $this->resolved_at,
            'status' => $this->status,
        ];
    }
}
