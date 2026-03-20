<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'sometimes|required|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'nullable|in:todo,in-progress,done',
            'priority' => 'nullable|in:high,medium,low',
            'due_at' => 'nullable|date',
        ];
    }
}
