<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\AdminAuditMiddleware;
use App\Http\Middleware\SetLocaleFromRequest;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'admin.audit' => AdminAuditMiddleware::class,
        ]);

        $middleware->appendToGroup('api', SetLocaleFromRequest::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*');
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            return response()->json([
                'message' => (string) ($e->getMessage() ?: 'The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Forbidden.',
            ], 403);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            return response()->json([
                'message' => 'Not Found.',
            ], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            $status = (int) $e->getStatusCode();
            $message = $e->getMessage();

            if ($status === 404 && $message === '') {
                $message = 'Not Found.';
            }
            if ($status === 429 && $message === '') {
                $message = 'Too Many Requests.';
            }
            if ($status === 403 && $message === '') {
                $message = 'Forbidden.';
            }
            if ($status === 401 && $message === '') {
                $message = 'Unauthenticated.';
            }

            return response()->json([
                'message' => $message !== '' ? $message : 'Error.',
            ], $status);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            $message = config('app.debug') ? $e->getMessage() : 'Server Error.';

            return response()->json([
                'message' => $message,
            ], 500);
        });

        //
    })->create();
