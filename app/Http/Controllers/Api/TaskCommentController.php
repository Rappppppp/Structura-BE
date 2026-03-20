<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreTaskCommentRequest;
use App\Http\Requests\UpdateTaskCommentRequest;
use App\Http\Resources\TaskCommentResource;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskCommentController extends ApiController
{
    public function index(Request $request, Task $task)
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

        $comments = $task->comments()->with(['user'])->get();

        return $this->success(TaskCommentResource::collection($comments), 'Comments retrieved');
    }

    public function store(Request $request, Task $task)
    {
        // Check authorization: user must have access to the task
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $isProjectMember = $task->project->team()->where('user_id', $user->id)->exists();
            $isAssignee = $task->assigned_to === $user->id;

            if (! $isProjectMember && ! $isAssignee) {
                return $this->error('Unauthorized to comment on this task', 403);
            }
        }

        $validated = $request->validate([
            'content' => 'required|string|min:1',
        ]);

        $comment = DB::transaction(function () use ($task, $validated, $user) {
            return TaskComment::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'content' => $validated['content'],
            ]);
        });

        $comment->load('user');

        return $this->success(new TaskCommentResource($comment), 'Comment created', 201);
    }

    public function destroy(Request $request, Task $task, TaskComment $comment)
    {
        // Check authorization: only the comment author or admin can delete
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin' && $comment->user_id !== $user->id) {
            return $this->error('Unauthorized to delete this comment', 403);
        }

        if ($comment->task_id !== $task->id) {
            return $this->error('Comment does not belong to this task', 400);
        }

        $comment->delete();

        return $this->success([], 'Comment deleted', 204);
    }
}
