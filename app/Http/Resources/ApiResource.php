<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\JsonResponse;

class ApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    public static function successResponse($data = null, $message = null, $token = null, $statusCode): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'status_code' => $statusCode,
            'data' => $data,
            'token' => $token,
            'message' => $message,
        ], $statusCode);
    }

    public static function errorResponse($message, $statusCode = null, $errors = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'status_code' => $statusCode,
            'errors' => $errors,
            'message' => $message,
        ], $statusCode);
    }

    public static function validationErrorResponse($errors, $message = 'Validation error', $statusCode = null): JsonResponse
    {
        return self::errorResponse($message, $statusCode, $errors);
    }
}
