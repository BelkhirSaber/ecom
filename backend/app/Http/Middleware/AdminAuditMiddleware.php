<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminAuditMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        $method = strtoupper($request->getMethod());
        $shouldLog = ! in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);

        $user = $request->user('sanctum') ?? $request->user();
        $userId = $user?->id;

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            if ($shouldLog) {
                $durationMs = (int) round((microtime(true) - $start) * 1000);

                $route = $request->route();

                Log::channel('audit')->info('admin.action', [
                    'user_id' => $userId,
                    'ip' => $request->ip(),
                    'method' => $method,
                    'path' => $request->path(),
                    'route_name' => $route?->getName(),
                    'route_action' => $route?->getActionName(),
                    'route_parameters' => $route?->parameters() ?? [],
                    'status' => 500,
                    'duration_ms' => $durationMs,
                    'exception' => $e::class,
                    'exception_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }

        if ($shouldLog) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            $route = $request->route();

            Log::channel('audit')->info('admin.action', [
                'user_id' => $userId,
                'ip' => $request->ip(),
                'method' => $method,
                'path' => $request->path(),
                'route_name' => $route?->getName(),
                'route_action' => $route?->getActionName(),
                'route_parameters' => $route?->parameters() ?? [],
                'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
                'duration_ms' => $durationMs,
            ]);
        }

        return $response;
    }
}
