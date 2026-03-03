<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: add permission checks
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'sometimes|required|exists:clients,id',
            'budget' => 'nullable|numeric|min:0',
            'progress' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:active,review,completed,on-hold',
            'deadline_at' => 'sometimes|required|date',
        ];
    }
}
