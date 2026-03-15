<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Invoice::query()->with(['client', 'project']);

        if ($search = $request->get('search')) {
            $query->where('invoice_id', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $payments = $query->latest()->paginate($perPage);

        // Totals apply client filter for context, but always show all status breakdowns
        $totalsQuery = Invoice::query();

        if ($clientId) {
            $totalsQuery->where('client_id', $clientId);
        }

        $totals = [
            'total_amount' => (clone $totalsQuery)->sum('amount'),
            'paid_amount' => (clone $totalsQuery)->where('status', 'paid')->sum('amount'),
            'pending_amount' => (clone $totalsQuery)->where('status', 'pending')->sum('amount'),
            'overdue_amount' => (clone $totalsQuery)->where('status', 'overdue')->sum('amount'),
        ];

        $resource = PaymentResource::collection($payments);

        return $this->success($resource, 'Payments retrieved')->withHeaders([
            'X-Total-Amount' => $totals['total_amount'],
            'X-Paid-Amount' => $totals['paid_amount'],
            'X-Pending-Amount' => $totals['pending_amount'],
            'X-Overdue-Amount' => $totals['overdue_amount'],
        ]);
    }

    public function show(Invoice $payment)
    {
        $payment->load(['client', 'project']);
        return $this->success(new PaymentResource($payment), 'Payment retrieved');
    }
}
