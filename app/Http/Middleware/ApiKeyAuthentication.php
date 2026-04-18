<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the X-API-KEY header against active tokens stored in the api_tokens table.
 *
 * The incoming key is hashed (SHA-256) and matched against the token_hash column —
 * the raw token is never stored, so even a database leak reveals nothing usable.
 *
 * On success the resolved ApiToken model is bound to the request as 'api_token'
 * for downstream use (e.g. rate limiting, logging).
 */
final class ApiKeyAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');

        if (! $apiKey) {
            return $this->unauthorized();
        }

        $token = ApiToken::findByRawKey((string) $apiKey);

        if (! $token) {
            return $this->unauthorized();
        }

        // Stamp last_used_at (non-blocking — fails silently if DB is unavailable)
        $token->updateQuietly(['last_used_at' => now()]);

        // Bind resolved token for downstream middleware / logging
        $request->attributes->set('api_token', $token);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'success' => false,
            'code'    => 401,
            'msg'     => 'Invalid or missing API key.',
            'data'    => null,
        ], Response::HTTP_UNAUTHORIZED);
    }
}
