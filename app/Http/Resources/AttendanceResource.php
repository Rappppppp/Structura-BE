<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'userName' => $this->whenLoaded('user', fn() => $this->user?->name),
            'scope' => $this->scope,
            'scopeId' => $this->scope_id,
            'scopeName' => $this->scope_name,
            'checkInTime' => $this->check_in_time?->toDateTimeString(),
            'checkOutTime' => $this->check_out_time?->toDateTimeString(),
            'checkInPhoto' => $this->check_in_photo,
            'checkOutPhoto' => $this->check_out_photo,
            'duration' => $this->duration,
            'createdAt' => $this->created_at?->toDateTimeString(),
            'updatedAt' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
