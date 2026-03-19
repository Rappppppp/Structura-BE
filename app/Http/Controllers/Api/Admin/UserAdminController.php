<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserAdminController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
        }

        $users = $query->latest()->paginate($perPage);

        return $this->success(UserResource::collection($users), 'Users retrieved');
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? 'user',
                'company' => $data['company'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
            ]);

            return $user;
        });

        return $this->success(data: new UserResource($user), message: 'User created', status: 201);
    }

    public function show(User $user)
    {
        return $this->success(new UserResource($user), 'User retrieved');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return $this->success(new UserResource($user), 'User updated');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->success([], 'User deleted', 204);
    }
}
