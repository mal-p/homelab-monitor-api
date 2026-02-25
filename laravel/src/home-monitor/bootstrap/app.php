<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\{Request, Response};
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Force JSON responses for API routes, even without `Accept: application/json` header
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Don't log unauthenticated requests
        $exceptions->dontReport(AuthenticationException::class);
        $exceptions->dontReport(RouteNotFoundException::class);
        // just return json 401
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(
                    ['errors' => ['auth' => ['Request unauthenticated.']]],
                    Response::HTTP_UNAUTHORIZED,
                );
            }
        });
        $exceptions->render(function (RouteNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(
                    ['errors' => ['auth' => ['Request unauthenticated.']]],
                    Response::HTTP_UNAUTHORIZED,
                );
            }
        });
    })->create();
