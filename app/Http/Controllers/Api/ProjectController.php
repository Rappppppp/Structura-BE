<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
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
        $query = Project::query()->with(['client']);

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $projects = $query->latest()->paginate($perPage);

        return $this->success(ProjectResource::collection($projects), 'Projects retrieved');
    }

    public function store(StoreProjectRequest $request)
    {
        $data = $request->validated();

        $project = DB::transaction(function () use ($data) {
            return Project::create($data);
        });

        return $this->success(new ProjectResource($project), 'Project created', 201);
    }

    public function show(Project $project)
    {
        $project->load(['client', 'tasks', 'invoices', 'team']);
        return $this->success(new ProjectResource($project), 'Project retrieved');
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->update($request->validated());
        return $this->success(new ProjectResource($project), 'Project updated');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return $this->success([], 'Project deleted', 204);
    }

    /**
     * Get analytics status for projects.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyticsStatus(Request $request)
    {
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $onHoldProjects = Project::where('status', 'on_hold')->count();

        $analytics = [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'on_hold_projects' => $onHoldProjects,
        ];

        return $this->success($analytics, 'Project analytics status retrieved');
    }
}
