<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class ImageGenerationController extends ApiController
{
    /**
     * Generate and store an image using OpenAI DALL-E API
     */
    public function generateWithOpenAI(Request $request, Project $project)
    {
        \Log::info('=== OPENAI IMAGE GENERATION REQUEST START ===', [
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ]);

        // Validate request
        $validated = $request->validate([
            'prompt' => 'required|string|max:4000',
        ]);

        $apiKey = config('services.openai.key');
        if (! $apiKey) {
            \Log::error('❌ OpenAI API key not configured');
            return $this->error('OpenAI API key not configured on server', 500);
        }

        try {
            \Log::info('🔄 Calling OpenAI DALL-E API', [
                'prompt' => substr($validated['prompt'], 0, 100),
                'model' => 'dall-e-3',
            ]);

            // Call OpenAI API
            $response = Http::timeout(60)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => 'dall-e-3',
                    'prompt' => $validated['prompt'],
                    'n' => 1,
                    'size' => '1024x1024',
                    'quality' => 'standard',
                ]);

            if (! $response->successful()) {
                \Log::error('❌ OpenAI API error', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return $this->error('Failed to generate image with OpenAI: '.$response->json()['error']['message'] ?? 'Unknown error', 500);
            }

            $data = $response->json();
            \Log::info('✅ OpenAI API response received', [
                'images_count' => count($data['data'] ?? []),
            ]);

            if (empty($data['data'][0]['url'])) {
                throw new \Exception('No image URL in OpenAI response');
            }

            $imageUrl = $data['data'][0]['url'];
            \Log::info('📥 Downloading image from OpenAI', ['url' => substr($imageUrl, 0, 50).'...']);

            // Download image from OpenAI
            $imageContent = Http::timeout(30)->get($imageUrl)->body();
            $binaryData = $imageContent;

            \Log::info('✅ Image downloaded', ['size' => strlen($binaryData)]);

            // Generate filename and path
            $filename = 'project-'.$project->id.'-'.Str::uuid().'.png';
            $fullPath = 'project-images/'.$project->id.'/'.$filename;

            \Log::info('📤 Storing image to disk', ['path' => $fullPath, 'size' => strlen($binaryData)]);

            // Store image to disk
            $stored = Storage::disk('public')->put($fullPath, $binaryData);
            \Log::info('Storage::put result', ['success' => $stored]);

            // Verify file was stored
            $exists = Storage::disk('public')->exists($fullPath);
            \Log::info('File verification', ['exists' => $exists, 'path' => $fullPath]);

            if (! $exists) {
                throw new \Exception('Failed to store image file');
            }

            \Log::info('✅ Image stored successfully to disk');

            // URL for public access
            $url = '/storage/'.$fullPath;

            // Create database record
            \Log::info('💾 Creating ProjectImage record', [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
            ]);

            $image = ProjectImage::create([
                'project_id' => $project->id,
                'generated_by' => Auth::id(),
                'prompt' => $validated['prompt'],
                'model' => 'dall-e-3',
                'quality' => 'standard',
                'path' => $fullPath,
                'url' => $url,
                'mime_type' => 'image/png',
                'size_bytes' => strlen($binaryData),
            ]);

            \Log::info('✅ ProjectImage created successfully', ['image_id' => $image->id]);

            return $this->success($image, 'Image generated and saved successfully', 201);
        } catch (\Exception $e) {
            \Log::error('❌ OpenAI image generation failed: '.$e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Failed to generate image: '.$e->getMessage(), 500);
        }
    }

    /**
     * Store a generated image from the Puter API
     */
    public function store(Request $request, Project $project)
    {
        \Log::info('=== IMAGE GENERATION REQUEST START ===', [
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'timestamp' => now(),
        ]);

        $validated = $request->validate([
            'image_data' => 'required|string',
            'prompt' => 'required|string|max:1000',
            'model' => 'nullable|string|max:100',
            'quality' => 'nullable|string|max:50',
        ]);

        \Log::info('Request validation passed', [
            'image_data_length' => strlen($validated['image_data']),
            'prompt' => substr($validated['prompt'], 0, 100),
        ]);

        try {
            // Extract base64 data if it's a data URL
            $imageData = $validated['image_data'];
            \Log::info('Extracting image data', ['starts_with_data_url' => str_starts_with($imageData, 'data:image')]);

            if (str_starts_with($imageData, 'data:image')) {
                $imageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $imageData);
                \Log::info('Extracted base64 from data URL', ['new_length' => strlen($imageData)]);
            }

            // Decode base64
            $binaryData = base64_decode($imageData, true);
            if (! $binaryData) {
                \Log::warning('❌ Failed to decode base64 image data');

                return $this->error('Invalid image data - failed to decode', 400);
            }

            \Log::info('✅ Base64 decoded successfully', ['binary_size' => strlen($binaryData)]);

            // Generate filename and path
            $filename = 'project-'.$project->id.'-'.Str::uuid().'.png';
            $fullPath = 'project-images/'.$project->id.'/'.$filename;

            \Log::info('📤 Storing image to disk', ['path' => $fullPath, 'size' => strlen($binaryData)]);

            // Store image directly to disk
            $stored = Storage::disk('public')->put($fullPath, $binaryData);
            \Log::info('Storage::put result', ['success' => $stored]);

            // Verify file was stored
            $exists = Storage::disk('public')->exists($fullPath);
            \Log::info('File verification', ['exists' => $exists, 'path' => $fullPath]);

            if (! $exists) {
                throw new \Exception('Failed to store image file - file does not exist after write');
            }

            \Log::info('✅ Image stored successfully to disk');

            // URL for public access
            $url = '/storage/'.$fullPath;

            // Create database record
            \Log::info('💾 Creating ProjectImage record', [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
            ]);

            $image = ProjectImage::create([
                'project_id' => $project->id,
                'generated_by' => Auth::id(),
                'prompt' => $validated['prompt'],
                'model' => $validated['model'] ?? 'gpt-image-1',
                'quality' => $validated['quality'] ?? 'medium',
                'path' => $fullPath,
                'url' => $url,
                'mime_type' => 'image/png',
                'size_bytes' => strlen($binaryData),
            ]);

            \Log::info('✅ ProjectImage created successfully', ['image_id' => $image->id, 'all_fields' => $image->toArray()]);

            return $this->success($image, 'Image generated and saved successfully', 201);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('❌ Image generation failed: '.$e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Failed to save image: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get project images
     */
    public function index(Request $request, Project $project)
    {

        $images = $project->images()
            ->with('generator:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $images->items(),
            'pagination' => [
                'total' => $images->total(),
                'per_page' => $images->perPage(),
                'current_page' => $images->currentPage(),
                'last_page' => $images->lastPage(),
            ],
            'message' => 'Project images retrieved',
        ]);
    }

    /**
     * Delete a generated image
     */
    public function destroy(Request $request, Project $project, ProjectImage $image)
    {
        // Verify image belongs to project
        if ($image->project_id !== $project->id) {
            return $this->error('Image not found', 404);
        }

        // Check authorization: user must be admin or assigned to this project
        $user = $request->user();
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $isAssigned = $project->team()->where('user_id', $user->id)->exists();
            if (! $isAssigned) {
                return $this->error('Unauthorized to delete this image', 403);
            }
        }


        try {
            // Delete file from storage
            if ($image->path && Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }

            // Delete database record
            $image->delete();

            return $this->success([], 'Image deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete image: '.$e->getMessage(), 500);
        }
    }
}
