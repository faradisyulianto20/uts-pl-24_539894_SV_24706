<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') ? null : '/login',
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function ($response, \Throwable $e, Request $request) {
            if ($e instanceof ValidationException) {
                $user = $request->user();
                Log::warning('request.validation_failed', [
                    'actor_user_id' => $user?->id,
                    'actor_role' => $user?->role,
                    'action' => 'validate_request',
                    'reason' => 'validation_failed',
                    'fields' => array_keys($e->errors()),
                    'route' => $request->route()?->getName() ?? $request->path(),
                ]);
            }

            return $response;
        });

        $exceptions->report(function (QueryException $e) {
            $request = request();
            $user = $request?->user();
            Log::error('database.query_failed', [
                'actor_user_id' => $user?->id,
                'actor_role' => $user?->role,
                'action' => 'query_database',
                'reason' => 'database_unreachable',
                'exception' => class_basename($e),
            ]);
        });
    })->create();
