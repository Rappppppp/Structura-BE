<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectFileController extends ApiController
{
    public function index(Project $project)
    {
        $files = $project->projectFiles()
            ->with('uploader:id,name')
            ->latest()
            ->get()
            ->map(function (ProjectFile $file) {
                return [
                    'id' => $file->id,
                    'name' => $file->name,
                    'mime_type' => $file->mime_type,
                    'size_bytes' => $file->size_bytes,
                    'url' => asset('storage/'.$file->path),
                    'uploaded_by' => $file->uploader?->name,
                    'created_at' => $file->created_at?->toDateTimeString(),
                ];
            });

        return $this->success($files, 'Project files retrieved');
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'file' => 'required|file|max:51200|mimes:pdf,dwg,dxf,doc,docx,png,jpg,jpeg,webp',
        ]);

        $uploadedFile = $data['file'];
        $storedPath = $uploadedFile->store('project-files/'.$project->id, 'public');

        $file = ProjectFile::query()->create([
            'project_id' => $project->id,
            'uploaded_by' => $request->user()?->id,
            'name' => $uploadedFile->getClientOriginalName(),
            'path' => $storedPath,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size_bytes' => $uploadedFile->getSize(),
        ]);

        return $this->success([
            'id' => $file->id,
            'name' => $file->name,
            'mime_type' => $file->mime_type,
            'size_bytes' => $file->size_bytes,
            'url' => asset('storage/'.$file->path),
            'uploaded_by' => $request->user()?->name,
            'created_at' => $file->created_at?->toDateTimeString(),
        ], 'File uploaded', 201);
    }

    public function destroy(Project $project, ProjectFile $file)
    {
        if ($file->project_id !== $project->id) {
            return $this->error('File does not belong to this project', 404);
        }

        Storage::disk('public')->delete($file->path);
        $file->delete();

        return $this->success([], 'File deleted');
    }
}
