<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ChatRoomResource;
use App\Models\ChatRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatRoomAdminController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = ChatRoom::query();

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $rooms = $query->latest()->paginate($perPage);

        return $this->success(ChatRoomResource::collection($rooms), 'Chat rooms retrieved');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
        ]);

        $room = DB::transaction(function () use ($data) {
            return ChatRoom::create($data);
        });

        return $this->success(new ChatRoomResource($room), 'Chat room created', 201);
    }

    public function show(ChatRoom $chat_room)
    {
        return $this->success(new ChatRoomResource($chat_room), 'Chat room retrieved');
    }

    public function update(Request $request, ChatRoom $chat_room)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $chat_room->update($data);

        return $this->success(new ChatRoomResource($chat_room), 'Chat room updated');
    }

    public function destroy(ChatRoom $chat_room)
    {
        $chat_room->delete();
        return $this->success([], 'Chat room deleted', 204);
    }
}
