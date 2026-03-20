<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'clients' => $this->whenLoaded('clients', function () {
                return ClientResource::collection($this->clients);
            }),
            'budget' => (float) $this->budget,
            'progress' => (float) $this->progress,
            'status' => $this->status,
            'deadline_at' => $this->deadline_at?->toDateTimeString(),
            'pending_tasks' => $this->when(isset($this->pending_tasks), $this->pending_tasks),
            'team_count' => $this->team_count ?? 0,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
