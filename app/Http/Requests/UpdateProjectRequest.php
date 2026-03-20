<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow any authenticated user to update projects
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'client_ids' => 'sometimes|required|array|min:1',
            'client_ids.*' => 'uuid|exists:clients,id',
            'budget' => 'nullable|numeric|min:0',
            'progress' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:active,review,completed,on-hold',
            'deadline_at' => 'sometimes|required|date',
        ];
    }
}
