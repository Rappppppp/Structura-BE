<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Invoice::query()->with(['client', 'project']);

        // Filter by role: non-admins only see payments for their clients or projects
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $query->where(function ($q) use ($user) {
                // Show payments for clients the user owns
                $q->whereHas('client', function ($subQ) use ($user) {
                    $subQ->where('account_owner_id', $user->id);
                })
                // Or show payments for projects the user is part of
                    ->orWhereHas('project', function ($subQ) use ($user) {
                        $subQ->whereHas('team', function ($teamQ) use ($user) {
                            $teamQ->where('user_id', $user->id);
                        });
                    });
            });
        }

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

        if ($user && strtolower((string) $user->role) !== 'admin') {
            $totalsQuery->where(function ($q) use ($user) {
                // Show payments for clients the user owns
                $q->whereHas('client', function ($subQ) use ($user) {
                    $subQ->where('account_owner_id', $user->id);
                })
                // Or show payments for projects the user is part of
                    ->orWhereHas('project', function ($subQ) use ($user) {
                        $subQ->whereHas('team', function ($teamQ) use ($user) {
                            $teamQ->where('user_id', $user->id);
                        });
                    });
            });
        }

        if (($clientId = $request->get('client_id'))) {
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

    public function show(Request $request, Invoice $payment)
    {
        // Check authorization: user must be admin, payment client owner, or project team member
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $isClientOwner = $payment->client->account_owner_id === $user->id;
            $isProjectMember = $payment->project->team()->where('user_id', $user->id)->exists();

            if (! $isClientOwner && ! $isProjectMember) {
                return $this->error('Unauthorized to view this payment', 403);
            }
        }

        $payment->load(['client', 'project']);

        return $this->success(new PaymentResource($payment), 'Payment retrieved');
    }
}
