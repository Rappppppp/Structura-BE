<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => 'required|string|unique:invoices,invoice_id',
            'project_id' => 'required|exists:projects,id',
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:paid,pending,overdue',
            'due_date' => 'required|date',
            'paid_at' => 'nullable|date',
            'contract_value' => 'nullable|numeric|min:0',
        ];
    }
}
