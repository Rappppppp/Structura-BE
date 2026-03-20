<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Client::query()->withCount('projects');

        // Filter by role: non-admins only see clients they own
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $query->where('account_owner_id', $user->id);
        }

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('contact_person', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $clients = $query->latest()->paginate($perPage);

        return $this->success(ClientResource::collection($clients), 'Clients retrieved');
    }

    public function store(StoreClientRequest $request)
    {
        $data = $request->validated();

        $client = DB::transaction(function () use ($data) {
            return Client::create($data);
        });

        return $this->success(new ClientResource($client), 'Client created', 201);
    }

    public function show(Request $request, Client $client)
    {
        // Check authorization: user must be admin or own this client
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin' && $client->account_owner_id !== $user->id) {
            return $this->error('Unauthorized to view this client', 403);
        }

        $client->load(['projects', 'invoices']);

        return $this->success(new ClientResource($client), 'Client retrieved');
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        // Check authorization: user must be admin or own this client
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin' && $client->account_owner_id !== $user->id) {
            return $this->error('Unauthorized to update this client', 403);
        }

        $client->update($request->validated());

        return $this->success(new ClientResource($client), 'Client updated');
    }

    public function destroy(Request $request, Client $client)
    {
        // Check authorization: user must be admin or own this client
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin' && $client->account_owner_id !== $user->id) {
            return $this->error('Unauthorized to delete this client', 403);
        }

        $client->delete();

        return $this->success([], 'Client deleted', 204);
    }
}
