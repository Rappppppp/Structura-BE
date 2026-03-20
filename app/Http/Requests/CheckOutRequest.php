<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => 'required|string', // base64 encoded photo (required for check-out)
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Photo is required for check-out',
            'photo.string' => 'Photo must be a valid base64 string',
        ];
    }
}
