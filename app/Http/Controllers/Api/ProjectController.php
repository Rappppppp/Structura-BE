<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Project::query()->with(['clients'])->withCount('team');

        // Filter by role: non-admins only see projects they're assigned to
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $query->whereHas('team', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->whereHas('clients', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });
        }

        $projects = $query->latest()->paginate($perPage);

        return $this->success(ProjectResource::collection($projects), 'Projects retrieved');
    }

    public function store(StoreProjectRequest $request)
    {
        $data = $request->validated();
        $clientIds = $data['client_ids'];
        unset($data['client_ids']);

        $project = DB::transaction(function () use ($data, $clientIds) {
            $project = Project::create($data);
            $project->clients()->attach($clientIds);
            return $project->load('clients');
        });

        return $this->success(new ProjectResource($project), 'Project created', 201);
    }

    public function show(Request $request, Project $project)
    {
        // Check authorization: user must be admin or assigned to this project
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $isAssigned = $project->team()->where('user_id', $user->id)->exists();
            if (! $isAssigned) {
                return $this->error('Unauthorized to view this project', 403);
            }
        }

        $project->load(['clients', 'tasks', 'invoices', 'team'])->loadCount('team');

        return $this->success(new ProjectResource($project), 'Project retrieved');
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        // Check authorization: user must be admin or manager of this project
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            if (strtolower((string) $user->role) !== 'project_manager') {
                $projectUserRole = $project->projectUserRoles()
                    ->where('user_id', $user->id)
                    ->first();

                if (! $projectUserRole || ! in_array($projectUserRole->base_role, ['admin'])) {
                    return $this->error('Unauthorized to update this project', 403);
                }
            }
        }

        $data = $request->validated();
        
        if (isset($data['client_ids'])) {
            $clientIds = $data['client_ids'];
            unset($data['client_ids']);
            $project->clients()->sync($clientIds);
        }

        $project->update($data);

        return $this->success(new ProjectResource($project->load('clients')), 'Project updated');
    }

    public function destroy(Request $request, Project $project)
    {
        // Check authorization: only admins and project managers can delete
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin' && strtolower((string) $user->role) !== 'project_manager') {
            return $this->error('Unauthorized to delete this project', 403);
        }

        $project->delete();

        return $this->success([], 'Project deleted');
    }

    /**
     * Get analytics status for projects.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyticsStatus(Request $request)
    {
        $query = Project::query();

        // Filter by role: non-admins only see analytics for their projects
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $query->whereHas('team', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $totalProjects = (clone $query)->count();
        $activeProjects = (clone $query)->where('status', 'active')->count();
        $completedProjects = (clone $query)->where('status', 'completed')->count();
        $onHoldProjects = (clone $query)->where('status', 'on-hold')->count();

        $analytics = [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'on_hold_projects' => $onHoldProjects,
        ];

        return $this->success($analytics, 'Project analytics status retrieved');
    }
}
