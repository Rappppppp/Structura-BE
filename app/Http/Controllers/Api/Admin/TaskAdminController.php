<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskAdminController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Task::query()->with(['project', 'assignee']);

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
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

        $task->project->updateProgressFromTasks();

        return $this->success(new TaskResource($task), 'Task created', 201);
    }

    public function show(Task $task)
    {
        $task->load(['project', 'assignee']);
        return $this->success(new TaskResource($task), 'Task retrieved');
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->update($request->validated());
        $task->project->updateProgressFromTasks();
        return $this->success(new TaskResource($task), 'Task updated');
    }

    public function destroy(Task $task)
    {
        $task->delete();
        $task->project->updateProgressFromTasks();
        return $this->success([], 'Task deleted', 204);
    }
}
