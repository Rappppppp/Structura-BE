<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scope' => 'required|in:global,project,team',
            'scope_id' => 'nullable|uuid',
            'photo' => 'required|string', // base64 encoded photo
        ];
    }

    public function messages(): array
    {
        return [
            'scope.required' => 'Attendance scope is required',
            'scope.in' => 'Attendance scope must be global, project, or team',
            'photo.required' => 'Photo is required for check-in',
            'photo.string' => 'Photo must be a valid base64 string',
        ];
    }
}
