<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Task::query()->with(['project', 'assignee']);

        // Filter by role: non-admins only see tasks from their projects or assigned to them
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $query->where(function ($q) use ($user) {
                // Show tasks from projects the user is part of
                $q->whereHas('project', function ($subQ) use ($user) {
                    $subQ->whereHas('team', function ($teamQ) use ($user) {
                        $teamQ->where('user_id', $user->id);
                    });
                })
                // Or show tasks assigned to the user
                    ->orWhere('assigned_to', $user->id);
            });
        }

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($projectId = $request->get('project_id')) {
            $query->where('project_id', $projectId);
        }

        $tasks = $query->latest()->paginate($perPage);

        return $this->success(TaskResource::collection($tasks), 'Tasks retrieved');
    }

    public function store(StoreTaskRequest $request)
    {
        $data = $request->validated();

        $task = DB::transaction(function () use ($data) {
            return Task::create($data);
        });

        // Update project progress if needed (don't override manual updates)
        $task->project->updateProgressFromTasks();

        return $this->success(new TaskResource($task), 'Task created', 201);
    }

    public function show(Request $request, Task $task)
    {
        // Check authorization: user must be admin, project team member, or assignee
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $isProjectMember = $task->project->team()->where('user_id', $user->id)->exists();
            $isAssignee = $task->assigned_to === $user->id;

            if (! $isProjectMember && ! $isAssignee) {
                return $this->error('Unauthorized to view this task', 403);
            }
        }

        $task->load(['project', 'assignee']);

        return $this->success(new TaskResource($task), 'Task retrieved');
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        // Check authorization: user must be admin, project manager, or assignee
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin' && strtolower((string) $user->role) !== 'project_manager') {
            if ($task->assigned_to !== $user->id) {
                return $this->error('Unauthorized to update this task', 403);
            }
        }

        $task->update($request->validated());
        $task->project->updateProgressFromTasks();

        return $this->success(new TaskResource($task), 'Task updated');
    }

    public function destroy(Request $request, Task $task)
    {
        // Check authorization: only admins and project managers can delete
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin' && strtolower((string) $user->role) !== 'project_manager') {
            return $this->error('Unauthorized to delete this task', 403);
        }

        $task->delete();
        $task->project->updateProgressFromTasks();

        return $this->success([], 'Task deleted', 204);
    }
}
