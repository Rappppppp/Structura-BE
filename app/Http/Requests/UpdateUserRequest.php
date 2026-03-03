<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes','required','email','max:255', Rule::unique('users')->ignore($userId)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
        ];
    }
}
