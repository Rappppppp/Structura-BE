<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CommunicationResource;
use App\Models\ChatRoom;
use Illuminate\Http\Request;

class CommunicationController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = ChatRoom::query()->with('project')->withCount('messages');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($projectId = $request->get('project_id')) {
            $query->where('project_id', $projectId);
        }

        $rooms = $query->latest()->paginate($perPage);

        return $this->success(CommunicationResource::collection($rooms), 'Chat rooms retrieved');
    }

    public function show(ChatRoom $communication)
    {
        $perPage = (int) request()->get('per_page', 20);

        $communication->load('project');
        $communication->setRelation(
            'messages',
            $communication->messages()->with('user')->paginate($perPage)
        );

        return $this->success(new CommunicationResource($communication), 'Chat room retrieved');
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
