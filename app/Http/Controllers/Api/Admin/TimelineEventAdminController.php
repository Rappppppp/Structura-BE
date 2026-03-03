<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\TimelineEventResource;
use App\Models\TimelineEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimelineEventAdminController extends ApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = TimelineEvent::query();

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $events = $query->latest()->paginate($perPage);

        return $this->success(TimelineEventResource::collection($events), 'Timeline events retrieved');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
        ]);

        $event = DB::transaction(function () use ($data) {
            return TimelineEvent::create($data);
        });

        return $this->success(new TimelineEventResource($event), 'Timeline event created', 201);
    }

    public function show(TimelineEvent $timeline_event)
    {
        return $this->success(new TimelineEventResource($timeline_event), 'Timeline event retrieved');
    }

    public function update(Request $request, TimelineEvent $timeline_event)
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'sometimes|required|date',
        ]);

        $timeline_event->update($data);

        return $this->success(new TimelineEventResource($timeline_event), 'Timeline event updated');
    }

    public function destroy(TimelineEvent $timeline_event)
    {
        $timeline_event->delete();
        return $this->success([], 'Timeline event deleted', 204);
    }
}
