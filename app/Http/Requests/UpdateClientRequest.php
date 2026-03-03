<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: wire permissions
    }

    public function rules(): array
    {
        $clientId = $this->route('client')?->id;

        return [
            'name' => 'sometimes|required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => ['sometimes','required','email','max:255', Rule::unique('clients')->ignore($clientId)],
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'active_projects' => 'nullable|integer|min:0',
            'total_value' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,review,completed,on-hold',
            'account_owner_id' => 'nullable|exists:users,id',
        ];
    }
}
