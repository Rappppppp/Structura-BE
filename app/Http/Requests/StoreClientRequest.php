<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: wire permissions
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:clients,email',
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'active_projects' => 'nullable|integer|min:0',
            'total_value' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,review,completed,on-hold',
            'account_owner_id' => 'nullable|exists:users,id',
        ];
    }
}
