<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\TeamMemberResource;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamMemberAdminController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = TeamMember::query()->with('user');

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $members = $query->latest()->paginate($perPage);

        return $this->success(TeamMemberResource::collection($members), 'Team members retrieved');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:255',
        ]);

        $member = DB::transaction(function () use ($data) {
            return TeamMember::create($data);
        });

        return $this->success(new TeamMemberResource($member), 'Team member created', 201);
    }

    public function show(TeamMember $team_member)
    {
        return $this->success(new TeamMemberResource($team_member->load('user')), 'Team member retrieved');
    }

    public function update(Request $request, TeamMember $team_member)
    {
        $data = $request->validate([
            'role' => 'sometimes|required|string|max:255',
            'avatar' => 'nullable|string|max:255',
        ]);

        $team_member->update($data);

        return $this->success(new TeamMemberResource($team_member), 'Team member updated');
    }

    public function destroy(TeamMember $team_member)
    {
        $team_member->delete();
        return $this->success([], 'Team member deleted', 204);
    }
}
