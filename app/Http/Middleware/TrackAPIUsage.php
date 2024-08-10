<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiUsage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackAPIUsage
{
    public function __construct(protected Router $router) {}

    /**
     * Handle an incoming request and track API usage.
     *
     * This middleware logs basic API usage data for the authenticated user,
     * including the endpoint accessed, HTTP method used, response status,
     * and response time, but only for valid routes. It includes error handling
     * to prevent issues with logging from affecting the API's operation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        try {
            if ($this->isValidRoute($request)) {
                $this->logApiUsage($request, $response, $startTime);
            }
        } catch (Throwable $e) {
            Log::error('Failed to log API usage: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->path(),
            ]);
        }

        return $response;
    }

    /**
     * Check if the current route is registered in the application.
     */
    private function isValidRoute(Request $request): bool
    {
        return $this->router->getRoutes()->match($request) !== null;
    }

    /**
     * Log the API usage for the current request.
     */
    private function logApiUsage(Request $request, Response $response, float $startTime): void
    {
        if (! $request->user()) {
            return;
        }

        ApiUsage::create([
            'user_id' => $request->user()->id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'response_status' => $response->getStatusCode(),
            'response_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
        ]);
    }
}
