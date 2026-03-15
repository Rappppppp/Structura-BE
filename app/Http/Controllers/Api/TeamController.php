<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\TeamMemberResource;
use App\Models\TeamMember;
use Illuminate\Http\Request;

class TeamController extends ApiController
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

        if ($projectId = $request->get('project_id')) {
            $query->whereHas('user', function ($q) use ($projectId) {
                $q->whereHas('projects', function ($q2) use ($projectId) {
                    $q2->where('projects.id', $projectId);
                });
            });
        }

        $members = $query->latest()->paginate($perPage);

        return $this->success(TeamMemberResource::collection($members), 'Team members retrieved');
    }

    public function show(TeamMember $team)
    {
        $team->load('user');
        return $this->success(new TeamMemberResource($team), 'Team member retrieved');
    }
}
