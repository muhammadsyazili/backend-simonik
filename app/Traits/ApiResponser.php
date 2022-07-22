<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponser
{
    /**
     * Format API response
     *
     * @param boolean $status
     * @param int $code
     * @param string|null $message
     * @param mixed $data
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function APIResponse($status, $code, $message, $data, $errors) : JsonResponse
    {
        return response()->json([
            'status' => $status,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $code);
    }
}