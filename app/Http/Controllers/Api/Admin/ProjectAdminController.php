<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectAdminController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Project::query()->with(['clients']);

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
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

    public function show(Project $project)
    {
        $project->load(['clients', 'tasks', 'invoices', 'team']);
        return $this->success(new ProjectResource($project), 'Project retrieved');
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $data = $request->validated();
        
        if (isset($data['client_ids'])) {
            $clientIds = $data['client_ids'];
            unset($data['client_ids']);
            $project->clients()->sync($clientIds);
        }

        $project->update($data);
        return $this->success(new ProjectResource($project->load('clients')), 'Project updated');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return $this->success([], 'Project deleted', 204);
    }
}
