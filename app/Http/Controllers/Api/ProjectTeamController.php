<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreProjectTeamRequest;
use App\Http\Resources\ProjectTeamMemberResource;
use App\Models\Project;
use App\Models\User;

class ProjectTeamController extends ApiController
{
    /**
     * Get all team members of a project.
     */
    public function index(Project $project)
    {
        $teamMembers = $project->projectUserRoles()
            ->with('user')
            ->get();

        return $this->success(ProjectTeamMemberResource::collection($teamMembers), 'Project team retrieved');
    }

    /**
     * Add a user to the project team.
     */
    public function store(StoreProjectTeamRequest $request, Project $project)
    {
        $data = $request->validated();

        if ($project->team()->whereKey($data['user_id'])->exists()) {
            return $this->error('User is already a member of this project', 422);
        }

        $projectUserRole = $project->projectUserRoles()->create([
            'user_id' => $data['user_id'],
            'base_role' => $data['base_role'] ?? 'member',
            'specialty_role' => $data['specialty_role'] ?? null,
        ]);

        $projectUserRole->load('user');

        return $this->success(new ProjectTeamMemberResource($projectUserRole), 'Team member added to project', 201);
    }

    /**
     * Remove a user from the project team.
     */
    public function destroy(Project $project, User $user)
    {
        $isTeamMember = $project->team()->whereKey($user->id)->exists();

        if (! $isTeamMember) {
            return $this->error('User is not a member of this project', 404);
        }

        $project->team()->detach($user->id);

        return $this->success([], 'Team member removed from project', 200);
    }
}
