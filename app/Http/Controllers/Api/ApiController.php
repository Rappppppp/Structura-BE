<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller as BaseController;

class ApiController extends BaseController
{
    protected function success($data = [], string $message = 'OK', int $status = 200): JsonResponse
    {
        if ($data instanceof ResourceCollection) {
            $response = $data->response()->setStatusCode($status);
            $content = json_decode($response->getContent(), true);
            $content['message'] = $message;
            return $response->setData($content);
        }

        return response()->json(["message" => $message, 'data' => $data], $status);
    }

    protected function error(string $message = 'Error', int $status = 400, $errors = null): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => $errors], $status);
    }
}
