<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectTeamMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'phone_number' => $this->user?->phone_number,
                'company' => $this->user?->company,
            ],
            'base_role' => $this->base_role, // admin|member|viewer
            'specialty_role' => $this->specialty_role, // architect|engineer|pm|bim|etc
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
