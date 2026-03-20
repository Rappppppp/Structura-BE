<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CommunicationResource;
use App\Models\ChatRoom;
use App\Models\Project;
use Illuminate\Http\Request;

class CommunicationController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = ChatRoom::query()->with('project')->withCount('messages');

        // Filter by role: non-admins only see chat rooms for their projects
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $query->whereHas('project', function ($q) use ($user) {
                $q->whereHas('team', function ($teamQ) use ($user) {
                    $teamQ->where('user_id', $user->id);
                });
            });
        }

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($projectId = $request->get('project_id')) {
            $query->where('project_id', $projectId);
        }

        $rooms = $query->latest()->paginate($perPage);

        return $this->success(CommunicationResource::collection($rooms), 'Chat rooms retrieved');
    }

    public function show(Request $request, ChatRoom $communication)
    {
        // Check authorization: user must be admin or part of this project
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $isProjectMember = $communication->project->team()->where('user_id', $user->id)->exists();
            if (! $isProjectMember) {
                return $this->error('Unauthorized to view this chat room', 403);
            }
        }

        $perPage = (int) request()->get('per_page', 20);

        $communication->load('project');
        $communication->setRelation(
            'messages',
            $communication->messages()->with('user')->paginate($perPage)
        );

        return $this->success(new CommunicationResource($communication), 'Chat room retrieved');
    }

    public function storeRoom(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'nullable|string|max:255',
        ]);

        $existingRoom = ChatRoom::query()
            ->where('project_id', $data['project_id'])
            ->first();

        if ($existingRoom) {
            return $this->success(new CommunicationResource($existingRoom), 'Chat room retrieved');
        }

        $project = Project::query()->findOrFail($data['project_id']);

        $room = ChatRoom::query()->create([
            'project_id' => $data['project_id'],
            'name' => $data['name'] ?? $project->name.' Chat',
        ]);

        return $this->success(new CommunicationResource($room), 'Chat room created', 201);
    }

    public function storeMessage(Request $request, ChatRoom $communication)
    {
        $data = $request->validate([
            'content' => 'required|string',
        ]);

        $message = $communication->addMessage($request->user(), $data['content']);

        return $this->success([
            'id' => $message->id,
            'content' => $message->message,
            'created_at' => $message->created_at?->toDateTimeString(),
            'user' => [
                'id' => $request->user()?->id,
                'name' => $request->user()?->name,
            ],
        ], 'Message sent', 201);
    }
}
