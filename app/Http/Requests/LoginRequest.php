<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
            'email' => [
                'required',
                'email:rfc,dns',
                'lowercase',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'remember_me' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check rate limiting
            if ($this->isNotThrottled()) {
                return;
            }

            $validator->errors()->add('email', 'Too many login attempts. Please try again later.');
        });
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
    }

    /**
     * Check if the login attempt is throttled.
     */
    public function isNotThrottled(): bool
    {
        $key = 'login:' . $this->getClientIp();
        $maxAttempts = 5;
        $decayMinutes = 15;

        return ! RateLimiter::tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Check if the request has been throttled; throw if so.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! $this->isNotThrottled()) {
            $key = 'login:' . $this->getClientIp();
            RateLimiter::hit($key);
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($key),
                ]),
            ]);
        }

        RateLimiter::hit('login:' . $this->getClientIp());
    }

    /**
     * Get the client IP address for rate limiting.
     */
    public function getClientIp(): string
    {
        return $this->ip();
    }

    /**
     * Get the validated data.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // Remove remember_me from validated data if present
        unset($validated['remember_me']);

        return $validated;
    }

    /**
     * Check if user wants to be remembered.
     */
    public function shouldRemember(): bool
    {
        return (bool) $this->input('remember_me', false);
    }
}
