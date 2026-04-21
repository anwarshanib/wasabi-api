<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PatchSwaggerServer
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $appUrl  = rtrim(config('app.url'), '/');
        $content = $response->getContent();

        // Replace the static "/" server URL baked in by the OA\Server annotation
        // so Swagger UI targets the correct base (live or sandbox).
        $content = str_replace('"url":"/"',   '"url":"' . $appUrl . '"', $content);
        $content = str_replace('"url": "/"',  '"url": "' . $appUrl . '"', $content);

        $response->setContent($content);

        return $response;
    }
}
