<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the X-API-KEY header against the list of authorised keys
 * stored in config/api.php (sourced from API_KEYS .env variable).
 *
 * No database required — keys are static and managed via environment config.
 */
final class ApiKeyAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey    = $request->header('X-API-KEY');
        $validKeys = config('api.keys', []);

        if (empty($validKeys) || ! $apiKey || ! in_array($apiKey, $validKeys, strict: true)) {
            return response()->json([
                'success' => false,
                'code'    => 401,
                'msg'     => 'Invalid or missing API key.',
                'data'    => null,
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
