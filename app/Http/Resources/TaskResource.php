<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project?->id,
                    'name' => $this->project?->name,
                ];
            }),
            'assignee' => $this->whenLoaded('assignee', function () {
                return [
                    'id' => $this->assignee?->id,
                    'name' => $this->assignee?->name,
                ];
            }),
            'status' => $this->status,
            'priority' => $this->priority,
            'due_at' => $this->due_at?->toDateTimeString(),
            'assigned_to' => $this->assigned_to,
            'category' => $this->category,
            'subCategory' => $this->subCategory,
            'finishingType' => $this->finishingType,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
