<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TeamMemberResource;
use App\Models\TeamMember;
use Illuminate\Http\Request;

class TeamController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = TeamMember::query()->with('user');

        // Filter by role: non-admins only see team members from their projects
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $query->whereHas('user', function ($q) use ($user) {
                $q->whereHas('projects', function ($q2) use ($user) {
                    $q2->whereHas('team', function ($teamQ) use ($user) {
                        $teamQ->where('user_id', $user->id);
                    });
                });
            });
        }

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

    public function show(Request $request, TeamMember $team)
    {
        // Check authorization: user must be admin or part of the same projects
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $sharedProject = $user->projects()
                ->whereHas('team', function ($q) use ($team) {
                    $q->where('user_id', $team->user_id);
                })
                ->exists();

            if (! $sharedProject) {
                return $this->error('Unauthorized to view this team member', 403);
            }
        }

        $team->load('user');

        return $this->success(new TeamMemberResource($team), 'Team member retrieved');
    }
}
