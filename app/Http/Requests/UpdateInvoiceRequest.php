<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoiceId = $this->route('invoice')?->id;

        return [
            'invoice_id' => ['sometimes', 'required', 'string', Rule::unique('invoices', 'invoice_id')->ignore($invoiceId)],
            'project_id' => 'sometimes|required|exists:projects,id',
            'client_id' => 'sometimes|required|exists:clients,id',
            'amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:paid,pending,overdue',
            'due_date' => 'nullable|date',
            'paid_at' => 'nullable|date',
            'contract_value' => 'nullable|numeric|min:0',
        ];
    }
}
