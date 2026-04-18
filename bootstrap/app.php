<?php

use App\Exceptions\WasabiApiException;
use App\Http\Middleware\ApiKeyAuthentication;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.auth' => ApiKeyAuthentication::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Force JSON for all responses — this is an API-only application
        $exceptions->shouldRenderJsonWhen(fn (Request $request, \Throwable $e): bool => true);

        $exceptions->render(function (WasabiApiException $e): \Illuminate\Http\JsonResponse {
            return response()->json([
                'success' => false,
                'code'    => $e->getCode(),
                'msg'     => $e->getMessage(),
                'data'    => null,
            ], $e->getHttpStatus());
        });

        $exceptions->render(function (ValidationException $e): \Illuminate\Http\JsonResponse {
            return response()->json([
                'success' => false,
                'code'    => 422,
                'msg'     => 'Validation failed.',
                'data'    => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e): \Illuminate\Http\JsonResponse {
            return response()->json([
                'success' => false,
                'code'    => 401,
                'msg'     => 'Unauthenticated.',
                'data'    => null,
            ], 401);
        });

        $exceptions->render(function (NotFoundHttpException $e): \Illuminate\Http\JsonResponse {
            return response()->json([
                'success' => false,
                'code'    => 404,
                'msg'     => 'Resource not found.',
                'data'    => null,
            ], 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e): \Illuminate\Http\JsonResponse {
            return response()->json([
                'success' => false,
                'code'    => 405,
                'msg'     => 'Method not allowed.',
                'data'    => null,
            ], 405);
        });
    })->create();

