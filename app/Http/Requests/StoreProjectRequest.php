<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: add permission checks
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'budget' => 'required|numeric|min:0',
            'progress' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:active,review,completed,on-hold',
            'deadline_at' => 'required|date',
        ];
    }
}
