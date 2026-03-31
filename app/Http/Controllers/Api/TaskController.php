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


    public function bulkCreate(Request $request)
    {
        $data = $request->validate([
            'projectId' => 'required|uuid|exists:projects,id',
            'tasks' => 'required|array|min:1',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.assigned_to' => 'nullable|uuid|exists:users,id',
            'tasks.*.status' => 'nullable|in:todo,in-progress,done',
            'tasks.*.priority' => 'nullable|in:high,medium,low',
            'tasks.*.due_at' => 'nullable|date',
            'tasks.*.work_percentage' => 'nullable|numeric|min:0|max:100',
            'tasks.*.category' => 'required|in:structural,architectural',
            'tasks.*.subCategory' => 'required_if:tasks.*.category,architectural|nullable|in:masonry,plumbing,electrical,finishing',
            'tasks.*.finishingType' => 'required_if:tasks.*.subCategory,finishing|nullable|in:ceiling,painting,tiles,fixtures,facade,roofing',
        ]);

        $created = DB::transaction(function () use ($data) {
            $tasks = [];
            foreach ($data['tasks'] as $taskData) {
                $taskData['project_id'] = $data['projectId'];
                $tasks[] = Task::create($taskData);
            }
            // Optionally update project progress
            if (count($tasks) > 0) {
                $tasks[0]->project->updateProgressFromTasks();
            }
            return $tasks;
        });

        return $this->success(TaskResource::collection($created), 'Tasks created', 201);
    }

    public function show(Request $request, Task $task)
    {
        // Check authorization: user must be admin, project team member, or assignee
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $isProjectMember = $task->project->team()->where('user_id', $user->id)->exists();
            $isAssignee = $task->assigned_to === $user->id;

            if (!$isProjectMember && !$isAssignee) {
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

        $data = $request->validated();

        // Handle work_percentage specially - auto-mark as done if it reaches 100%
        if (isset($data['work_percentage'])) {
            $workPercentage = $data['work_percentage'];
            unset($data['work_percentage']);
            $task->update($data);
            $task->updateWorkPercentage($workPercentage);
        } else {
            $task->update($data);
        }

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
