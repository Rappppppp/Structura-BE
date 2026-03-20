<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow any authenticated user to create a project
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'uuid|exists:clients,id',
            'budget' => 'required|numeric|min:0',
            'progress' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:active,review,completed,on-hold',
            'deadline_at' => 'required|date',
        ];
    }
}
