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
            return $data->response()->setStatusCode($status)->setData(["message" => $message, 'data' => $data->resource]);
        }

        return response()->json(["message" => $message, 'data' => $data], $status);
    }

    protected function error(string $message = 'Error', int $status = 400, $errors = null): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => $errors], $status);
    }
}
