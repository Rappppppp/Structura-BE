<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Invoice::query()->with(['project']);

        // Filter by role: non-admins only see invoices for projects they're part of
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $query->whereHas('project', function ($subQ) use ($user) {
                $subQ->whereHas('team', function ($teamQ) use ($user) {
                    $teamQ->where('user_id', $user->id);
                });
            });
        }

        if ($search = $request->get('search')) {
            $query->where('invoice_id', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
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

    public function show(Request $request, Invoice $invoice)
    {
        // Check authorization: user must be admin or project team member
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $isProjectMember = $invoice->project->team()->where('user_id', $user->id)->exists();

            if (! $isProjectMember) {
                return $this->error('Unauthorized to view this invoice', 403);
            }
        }

        $invoice->load(['project']);

        return $this->success(new InvoiceResource($invoice), 'Invoice retrieved');
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        // Check authorization: user must be admin or project manager
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin' && strtolower((string) $user->role) !== 'project_manager') {
            return $this->error('Unauthorized to update this invoice', 403);
        }

        $invoice->update($request->validated());

        return $this->success(new InvoiceResource($invoice), 'Invoice updated');
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        // Check authorization: only admins and project managers can delete
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin' && strtolower((string) $user->role) !== 'project_manager') {
            return $this->error('Unauthorized to delete this invoice', 403);
        }

        $invoice->delete();

        return $this->success([], 'Invoice deleted', 204);
    }
}
