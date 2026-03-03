<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'role' => $this->role,
            'avatar' => $this->avatar,
            'projects_count' => $this->projects_count,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
