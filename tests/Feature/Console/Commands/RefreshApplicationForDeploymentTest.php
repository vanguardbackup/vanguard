<?php

use App\Console\Commands\RefreshApplicationForDeployment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->command = new RefreshApplicationForDeployment();

    // Create a new array cache store for testing
    $this->cache = Cache::store('array');
});

it('fails when in debug mode without force option', function () {
    config(['app.debug' => true]);

    $this->artisan(RefreshApplicationForDeployment::class)
        ->assertFailed();
});

it('succeeds when not in debug mode', function () {
    config(['app.debug' => false]);

    $this->artisan(RefreshApplicationForDeployment::class)
        ->assertSuccessful();
});

it('skips migrations when --skip-migrations option is used', function () {
    config(['app.debug' => false]);

    $this->artisan(RefreshApplicationForDeployment::class, ['--skip-migrations' => true])
        ->expectsOutputToContain('Skipping migrations.')
        ->assertSuccessful();
});

it('skips cache operations when --skip-cache option is used', function () {
    config(['app.debug' => false]);

    $this->artisan(RefreshApplicationForDeployment::class, ['--skip-cache' => true])
        ->expectsOutputToContain('Skipping cache operations.')
        ->assertSuccessful();
});

it('clears version cache when versions do not match', function () {
    config(['app.debug' => false]);

    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('get')->andReturn('1.0.0');

    $this->cache->put('vanguard_version', '0.9.0');

    $this->artisan(RefreshApplicationForDeployment::class)->assertSuccessful();

    expect($this->cache->has('vanguard_version'))->toBeFalse();
});

it('does not clear version cache when versions match', function () {
    config(['app.debug' => false]);

    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('get')->andReturn('1.0.0');

    $this->cache->put('vanguard_version', '1.0.0');

    $this->artisan(RefreshApplicationForDeployment::class)->assertSuccessful();

    expect($this->cache->has('vanguard_version'))->toBeTrue();
});

it('logs deployment details', function () {
    config(['app.debug' => false]);
    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('get')->andReturn('1.0.0');

    Log::shouldReceive('info')
        ->once()
        ->withArgs(fn ($message) => str_contains($message, 'Deployment completed. Version: 1.0.0'));

    $this->artisan(RefreshApplicationForDeployment::class)->assertSuccessful();
});
