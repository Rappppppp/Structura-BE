<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientAdminController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Client::query()->withCount('projects');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
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

    public function show(Client $client)
    {
        $client->load(['projects', 'invoices']);
        return $this->success(new ClientResource($client), 'Client retrieved');
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());
        return $this->success(new ClientResource($client), 'Client updated');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return $this->success([], 'Client deleted', 204);
    }
}
