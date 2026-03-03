<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint, no authorization needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-Z\s\'-]+$/i', // Name can contain letters, spaces, hyphens, apostrophes
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'unique:users,email',
                'lowercase',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'password_confirmation' => 'required|same:password',
            'role' => [
                'nullable',
                'string',
                'max:100',
                Rule::in(['user', 'admin', 'project_manager', 'architect', 'engineer']),
            ],
            'company' => [
                'nullable',
                'string',
                'min:2',
                'max:255',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'min:10',
                'max:20',
                'regex:/^\+?1?\d{9,19}$/', // International phone format
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.regex' => 'Name can only contain letters, spaces, hyphens, and apostrophes.',
            'name.min' => 'Name must be at least 2 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'email.lowercase' => 'Email address will be converted to lowercase.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Passwords do not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.mixed_case' => 'Password must contain both uppercase and lowercase letters.',
            'password.numbers' => 'Password must contain at least one number.',
            'password.symbols' => 'Password must contain at least one special character (!@#$%^&*).',
            'role.in' => 'Invalid role selected.',
            'phone_number.regex' => 'Please provide a valid phone number (10-20 digits, optionally starting with +1).',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'password_confirmation' => 'password confirmation',
            'phone_number' => 'phone number',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower($this->email),
            ]);
        }

        if ($this->has('phone_number')) {
            // Remove all non-numeric characters except +
            $this->merge([
                'phone_number' => preg_replace('/[^\d\+]/', '', (string) $this->phone_number),
            ]);
        }

        if ($this->has('name')) {
            // Capitalize name properly
            $this->merge([
                'name' => trim((string) $this->name),
            ]);
        }
    }

    /**
     * Get the validated data, with passwords excluded.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // Don't expose password_confirmation in validated output
        unset($validated['password_confirmation']);

        return $validated;
    }
}
