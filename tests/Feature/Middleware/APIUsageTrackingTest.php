<?php

declare(strict_types=1);

use App\Http\Middleware\TrackAPIUsage;
use App\Models\ApiUsage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->router = Mockery::mock(Router::class);
});

it('tracks api usage for authenticated users on valid routes', function (): void {
    $request = Request::create('/api/test', 'GET');
    $request->setUserResolver(fn () => $this->user);

    $this->router->shouldReceive('getRoutes->match')
        ->once()
        ->andReturn(true);

    $middleware = new TrackAPIUsage($this->router);

    $middleware->handle($request, function ($req): Response {
        return new Response('Test Response', 200);
    });

    expect(ApiUsage::count())->toBe(1);

    $apiUsage = ApiUsage::first();
    expect($apiUsage->user_id)->toBe($this->user->id)
        ->and($apiUsage->endpoint)->toBe('api/test')
        ->and($apiUsage->method)->toBe('GET')
        ->and($apiUsage->response_status)->toBe(200)
        ->and($apiUsage->response_time_ms)->toBeGreaterThanOrEqual(0);
});

it('does not track api usage for invalid routes', function (): void {
    $request = Request::create('/api/invalid', 'GET');
    $request->setUserResolver(fn () => $this->user);

    $this->router->shouldReceive('getRoutes->match')
        ->once()
        ->andReturn(null);

    $middleware = new TrackAPIUsage($this->router);

    $response = $middleware->handle($request, function ($req): Response {
        return new Response('Not Found', 404);
    });

    expect(ApiUsage::count())->toBe(0);
});

it('does not track api usage for unauthenticated users', function (): void {
    $request = Request::create('/api/test', 'GET');

    $this->router->shouldReceive('getRoutes->match')
        ->once()
        ->andReturn(true);

    $middleware = new TrackAPIUsage($this->router);

    $response = $middleware->handle($request, function ($req): Response {
        return new Response('Test Response', 200);
    });

    expect(ApiUsage::count())->toBe(0);
});

it('tracks api usage for different HTTP methods on valid routes', function (): void {
    $methods = ['GET', 'POST', 'PUT', 'DELETE'];

    foreach ($methods as $method) {
        ApiUsage::query()->delete(); // Clear previous records

        $request = Request::create('/api/test', $method);
        $request->setUserResolver(fn () => $this->user);

        $this->router->shouldReceive('getRoutes->match')
            ->once()
            ->andReturn(true);

        $middleware = new TrackAPIUsage($this->router);

        $middleware->handle($request, function ($req): Response {
            return new Response('Test Response', 200);
        });

        $apiUsage = ApiUsage::first();
        expect($apiUsage->method)->toBe($method)
            ->and(ApiUsage::count())->toBe(1);
    }
});

afterEach(function (): void {
    ApiUsage::query()->delete();
    Mockery::close();
});
