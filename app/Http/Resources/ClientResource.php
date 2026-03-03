<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'industry' => $this->industry,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'location' => $this->location,
            'active_projects' => $this->active_projects,
            'total_value' => (float) $this->total_value,
            'status' => $this->status,
            'account_owner' => $this->whenLoaded('accountOwner', function () {
                return [
                    'id' => $this->accountOwner?->id,
                    'name' => $this->accountOwner?->name,
                    'email' => $this->accountOwner?->email,
                ];
            }),
            'projects_count' => $this->when(isset($this->projects_count), $this->projects_count),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
