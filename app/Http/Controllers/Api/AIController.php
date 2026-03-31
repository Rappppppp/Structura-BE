<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AIController extends Controller
{
    public function timelinePredict(Request $request)
    {
        $validated = $request->validate([
            'projectId' => 'required|string',
            'projectTitle' => 'required|string',
            'tasks' => 'required|array',
            'tasks.*.name' => 'required|string',
            'tasks.*.category' => 'required|string',
            'tasks.*.subCategory' => 'nullable|string',
        ]);

        // Prepare prompt for OpenAI
        $prompt = "You are a construction project manager AI. Given a project title and a list of tasks (with categories and subcategories), estimate the number of days required for each task and the total project duration. Respond ONLY in this JSON format:\n\n{\n  \"tasks\": [\n    { \"name\": \"...\", \"estimated_days\": ... },\n    ...\n  ],\n  \"total_days\": ...,\n  \"notes\": \"...\"\n}\n\nProject Title: {$validated['projectTitle']}\nTasks:\n";
        foreach ($validated['tasks'] as $task) {
            $prompt .= "- {$task['name']} (Category: {$task['category']}, Subcategory: " . ($task['subCategory'] ?? 'N/A') . ")\n";
        }
        $prompt .= "\nEstimate the timeline as described above.";

        try {
            // Use OpenAI API (assume env('OPENAI_API_KEY') is set)
            $client = new \GuzzleHttp\Client();
            $openaiKey = env('OPENAI_API_KEY');
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $openaiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 512,
                ],
                'timeout' => 20,
            ]);
            $body = json_decode($response->getBody(), true);
            $aiContent = $body['choices'][0]['message']['content'] ?? null;
            // Extract JSON from AI response
            $json = null;
            if ($aiContent) {
                // Try to extract JSON block
                if (preg_match('/\{.*\}/s', $aiContent, $matches)) {
                    $json = json_decode($matches[0], true);
                } else {
                    $json = json_decode($aiContent, true);
                }
            }
            // Validate structure
            if (
                $json &&
                isset($json['tasks']) && is_array($json['tasks']) &&
                isset($json['total_days']) && is_numeric($json['total_days'])
            ) {
                // Sanitize tasks
                $tasks = array_map(function ($task) {
                    return [
                        'name' => (string)($task['name'] ?? ''),
                        'estimated_days' => (int)($task['estimated_days'] ?? 0),
                    ];
                }, $json['tasks']);
                $result = [
                    'tasks' => $tasks,
                    'total_days' => (int)$json['total_days'],
                    'notes' => isset($json['notes']) ? (string)$json['notes'] : null,
                ];
                return response()->json($result);
            }
        } catch (\Exception $e) {
            // Log error and fall back to mock
            \Log::error('OpenAI timelinePredict error: ' . $e->getMessage());
        }
    }

    public function taskSuggest(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string',
        ]);

        // Get StoreTaskRequest rules for schema
        $storeRules = (new \App\Http\Requests\StoreTaskRequest())->rules();
        // Remove project_id for suggestions (AI doesn't know the project id)
        unset($storeRules['project_id']);

        // Prepare a sanitized copy of the rules for validating AI suggestions.
        // Remove DB-dependent constraints (like exists: and unique:) so Validator
        // doesn't perform DB queries (which can fail if AI returns non-UUID ids).
        $sanitizedRules = [];
        foreach ($storeRules as $field => $rule) {
            if (is_string($rule)) {
                $parts = explode('|', $rule);
                $parts = array_values(array_filter($parts, function ($part) {
                    return !(strpos($part, 'exists:') === 0 || strpos($part, 'unique:') === 0);
                }));
                if (empty($parts)) {
                    $parts = ['nullable'];
                }
                $sanitizedRules[$field] = implode('|', $parts);
            } else {
                $sanitizedRules[$field] = $rule;
            }
        }

        // Build a schema description for the AI
        $fields = [];
        foreach ($storeRules as $field => $rule) {
            $fields[] = $field;
        }
        $fieldsList = implode(', ', $fields);

        $prompt = "You are an expert construction project manager AI. Given the following user prompt, suggest a list of tasks for a construction project. Each task must be a JSON object with the following fields: $fieldsList.\n\nField rules (for validation):\n";
        foreach ($storeRules as $field => $rule) {
            $prompt .= "- $field: $rule\n";
        }
        $prompt .= "\nUser prompt: {$validated['prompt']}\n\nRespond ONLY with a JSON array of tasks, no explanation.";

        try {
            $client = new \GuzzleHttp\Client();
            $openaiKey = env('OPENAI_API_KEY');
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $openaiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 1024,
                ],
                'timeout' => 20,
            ]);
            $body = json_decode($response->getBody(), true);
            $aiContent = $body['choices'][0]['message']['content'] ?? null;
            // Extract JSON from AI response
            $tasks = null;
            if ($aiContent) {
                if (preg_match('/\[.*\]/s', $aiContent, $matches)) {
                    $tasks = json_decode($matches[0], true);
                } else {
                    $tasks = json_decode($aiContent, true);
                }
            }
            // Validate and sanitize each task using StoreTaskRequest rules
            $validTasks = [];
            if (is_array($tasks)) {
                foreach ($tasks as $task) {
                    $validator = \Validator::make($task, $sanitizedRules);
                    if ($validator->passes()) {
                        // Only keep allowed fields
                        $validTasks[] = array_intersect_key($task, $storeRules);
                    }
                }
            }
            if (count($validTasks) > 0) {
                return response()->json(['tasks' => $validTasks]);
            }
        } catch (\Exception $e) {
            \Log::error('OpenAI taskSuggest error: ' . $e->getMessage());
        }
       
    }
}
