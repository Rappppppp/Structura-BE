<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceAdminController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Invoice::query()->with(['project', 'client']);

        if ($search = $request->get('search')) {
            $query->where('invoice_id', 'like', "%{$search}%");
        }

        $invoices = $query->latest()->paginate($perPage);

        return $this->success(InvoiceResource::collection($invoices), 'Invoices retrieved');
    }

    public function store(StoreInvoiceRequest $request)
    {
        $data = $request->validated();

        $invoice = DB::transaction(function () use ($data) {
            return Invoice::create($data);
        });

        return $this->success(new InvoiceResource($invoice), 'Invoice created', 201);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['project', 'client']);
        return $this->success(new InvoiceResource($invoice), 'Invoice retrieved');
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $invoice->update($request->validated());
        return $this->success(new InvoiceResource($invoice), 'Invoice updated');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return $this->success([], 'Invoice deleted', 204);
    }
}
