<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Provides consistent JSON response helpers for API controllers.
 *
 * Response envelope matches the Wasabi Card API structure:
 *   { success, code, msg, data }
 */
trait ApiResponse
{
    /**
     * Return a successful JSON response (HTTP 200).
     */
    protected function success(mixed $data = null, string $msg = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code'    => $code,
            'msg'     => $msg,
            'data'    => $data,
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Return an error JSON response.
     * $code is used as both the envelope code and the HTTP status.
     */
    protected function error(string $msg = 'Error', int $code = 400, mixed $data = null): JsonResponse
    {
        $httpStatus = ($code >= 100 && $code < 600) ? $code : 400;

        return response()->json([
            'success' => false,
            'code'    => $code,
            'msg'     => $msg,
            'data'    => $data,
        ], $httpStatus);
    }
}
