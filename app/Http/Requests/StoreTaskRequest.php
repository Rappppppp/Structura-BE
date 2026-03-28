<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'nullable|in:todo,in-progress,done',
            'priority' => 'nullable|in:high,medium,low',
            'due_at' => 'nullable|date',
            'work_percentage' => 'nullable|numeric|min:0|max:100',
            'category' => 'required|in:structural,architectural',
            'subCategory' => 'required_if:category,architectural|nullable|in:masonry,plumbing,electrical,finishing',
            'finishingType' => 'required_if:subCategory,finishing|nullable|in:ceiling,painting,tiles,fixtures,facade,roofing',
        ];
    }
}
