<?php

declare(strict_types=1);

use App\Console\Commands\FetchNewFeatures;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
});

it('successfully fetches and stores new features from remote', function (): void {
    Http::fake([
        'https://raw.githubusercontent.com/vanguardbackup/vanguard/main/new_features.json' => Http::response([
            [
                'title' => 'New Feature',
                'description' => 'This is a new feature',
                'version' => '1.1.0',
                'github_url' => 'https://github.com/vanguardbackup/vanguard/releases/tag/1.1.0',
            ],
        ], 200),
    ]);

    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(true);
    File::shouldReceive('get')->with(base_path('VERSION'))->andReturn('1.0.0');

    $this->artisan(FetchNewFeatures::class)
        ->assertSuccessful()
        ->expectsOutputToContain('New features fetched and stored successfully.');

    expect(Cache::get('latest_feature'))->toBe([
        'title' => 'New Feature',
        'description' => 'This is a new feature',
        'version' => '1.1.0',
        'github_url' => 'https://github.com/vanguardbackup/vanguard/releases/tag/1.1.0',
        'current_version' => '1.0.0',
    ]);
});

it('handles empty feature list from remote', function (): void {
    Http::fake([
        'https://raw.githubusercontent.com/vanguardbackup/vanguard/main/new_features.json' => Http::response([], 200),
    ]);

    $this->artisan(FetchNewFeatures::class)
        ->assertSuccessful()
        ->expectsOutputToContain('No new features found.');

    expect(Cache::get('latest_feature'))->toBeNull();
});

it('handles HTTP request failure', function (): void {
    Http::fake([
        'https://raw.githubusercontent.com/vanguardbackup/vanguard/main/new_features.json' => Http::response(null, 500),
    ]);

    $this->artisan(FetchNewFeatures::class)
        ->assertFailed()
        ->expectsOutput('Failed to fetch new features from remote: HTTP request returned status code 500');

    expect(Cache::get('latest_feature'))->toBeNull();
});

it('successfully fetches and stores new features from local file', function (): void {
    $features = [
        [
            'title' => 'Local Feature',
            'description' => 'This is a local feature',
            'version' => '1.2.0',
            'github_url' => 'https://github.com/vanguardbackup/vanguard/releases/tag/1.2.0',
        ],
    ];

    File::shouldReceive('exists')->with(base_path('new_features.json'))->andReturn(true);
    File::shouldReceive('get')->with(base_path('new_features.json'))->andReturn(json_encode($features));
    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(true);
    File::shouldReceive('get')->with(base_path('VERSION'))->andReturn('1.1.0');

    $this->artisan(FetchNewFeatures::class, ['--local' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('New features fetched and stored successfully.');

    expect(Cache::get('latest_feature'))->toBe([
        'title' => 'Local Feature',
        'description' => 'This is a local feature',
        'version' => '1.2.0',
        'github_url' => 'https://github.com/vanguardbackup/vanguard/releases/tag/1.2.0',
        'current_version' => '1.1.0',
    ]);
});

it('handles missing local file', function (): void {
    File::shouldReceive('exists')->with(base_path('new_features.json'))->andReturn(false);

    $this->artisan(FetchNewFeatures::class, ['--local' => true])
        ->assertFailed()
        ->expectsOutputToContain('Local new_features.json file not found in project root.');

    expect(Cache::get('latest_feature'))->toBeNull();
});

it('handles invalid JSON in local file', function (): void {
    File::shouldReceive('exists')->with(base_path('new_features.json'))->andReturn(true);
    File::shouldReceive('get')->with(base_path('new_features.json'))->andReturn('invalid json');

    $this->artisan(FetchNewFeatures::class, ['--local' => true])
        ->assertFailed()
        ->expectsOutputToContain('Failed to parse local new_features.json file.');

    expect(Cache::get('latest_feature'))->toBeNull();
});

it('uses default version when VERSION file is missing', function (): void {
    Http::fake([
        'https://raw.githubusercontent.com/vanguardbackup/vanguard/main/new_features.json' => Http::response([
            [
                'title' => 'New Feature',
                'description' => 'This is a new feature',
                'version' => '1.0.0',
                'github_url' => 'https://github.com/vanguardbackup/vanguard/releases/tag/1.0.0',
            ],
        ], 200),
    ]);

    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(false);

    $this->artisan(FetchNewFeatures::class)
        ->assertSuccessful()
        ->expectsOutputToContain('New features fetched and stored successfully.');

    expect(Cache::get('latest_feature')['current_version'])->toBe('0.0.0');
});

it('does not update cache if fetched version is older', function (): void {
    Cache::put('latest_feature', [
        'title' => 'Existing Feature',
        'description' => 'This is an existing feature',
        'version' => '1.2.0',
        'github_url' => 'https://github.com/vanguardbackup/vanguard/releases/tag/1.2.0',
        'current_version' => '1.1.0',
    ], now()->addWeek());

    Http::fake([
        'https://raw.githubusercontent.com/vanguardbackup/vanguard/main/new_features.json' => Http::response([
            [
                'title' => 'Older Feature',
                'description' => 'This is an older feature',
                'version' => '1.1.0',
                'github_url' => 'https://github.com/vanguardbackup/vanguard/releases/tag/1.1.0',
            ],
        ], 200),
    ]);

    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(true);
    File::shouldReceive('get')->with(base_path('VERSION'))->andReturn('1.1.0');

    $this->artisan(FetchNewFeatures::class)
        ->assertSuccessful()
        ->expectsOutputToContain('No new features to store.');

    expect(Cache::get('latest_feature')['version'])->toBe('1.2.0');
});

it('logs error when exception occurs', function (): void {
    Http::fake([
        'https://raw.githubusercontent.com/vanguardbackup/vanguard/main/new_features.json' => Http::response(null, 500),
    ]);

    Log::shouldReceive('error')->once()->with('Failed to fetch new features: Failed to fetch new features from remote: HTTP request returned status code 500');

    $this->artisan(FetchNewFeatures::class)
        ->assertFailed()
        ->expectsOutputToContain('Failed to fetch new features from remote: HTTP request returned status code 500');
});

it('handles invalid feature data', function (): void {
    Http::fake([
        'https://raw.githubusercontent.com/vanguardbackup/vanguard/main/new_features.json' => Http::response([
            [
                'title' => 'Invalid Feature',
                // missing 'description' and 'version'
            ],
        ], 200),
    ]);

    $this->artisan(FetchNewFeatures::class)
        ->assertSuccessful()
        ->expectsOutputToContain('No valid features found.');

    expect(Cache::get('latest_feature'))->toBeNull();
});

it('updates cache when fetched version is newer', function (): void {
    Cache::put('latest_feature', [
        'title' => 'Existing Feature',
        'description' => 'This is an existing feature',
        'version' => '1.1.0',
        'github_url' => 'https://github.com/vanguardbackup/vanguard/releases/tag/1.1.0',
        'current_version' => '1.0.0',
    ], now()->addWeek());

    Http::fake([
        'https://raw.githubusercontent.com/vanguardbackup/vanguard/main/new_features.json' => Http::response([
            [
                'title' => 'Newer Feature',
                'description' => 'This is a newer feature',
                'version' => '1.2.0',
                'github_url' => 'https://github.com/vanguardbackup/vanguard/releases/tag/1.2.0',
            ],
        ], 200),
    ]);

    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(true);
    File::shouldReceive('get')->with(base_path('VERSION'))->andReturn('1.1.0');

    $this->artisan(FetchNewFeatures::class)
        ->assertSuccessful()
        ->expectsOutputToContain('New features fetched and stored successfully.');

    expect(Cache::get('latest_feature')['version'])->toBe('1.2.0');
});
