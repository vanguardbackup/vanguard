<?php

declare(strict_types=1);

use App\Console\Commands\CheckVersion;
use Illuminate\Support\Facades\File;

it('it returns unknown if it cannot find the version file', function () {
    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(false);

    $this->artisan(CheckVersion::class)
        ->expectsOutputToContain('Unknown.')
        ->assertExitCode(0);
});

it('returns the current version number from the file', function () {
    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(true);
    File::shouldReceive('get')->with(base_path('VERSION'))->andReturn('1.0.0');

    $this->artisan(CheckVersion::class)
        ->expectsOutputToContain('The current version of Vanguard is: 1.0.0.')
        ->assertExitCode(0);
});

it('informs the user that they are using the latest version', function () {
    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(true);
    File::shouldReceive('get')->with(base_path('VERSION'))->andReturn('1.0.0');

    Http::fake([
        'https://api.github.com/repos/vanguardsh/vanguard/releases/latest' => Http::response([
            'tag_name' => '1.0.0',
            'published_at' => '2024-01-01T00:00:00Z',
        ], 200),
    ]);

    $this->artisan(CheckVersion::class, ['--check' => true])
        ->expectsOutputToContain('You are using the latest version of Vanguard.')
        ->assertExitCode(0);
});

it('warns the user that a new version is available', function () {
    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(true);
    File::shouldReceive('get')->with(base_path('VERSION'))->andReturn('1.0.0');

    Http::fake([
        'https://api.github.com/repos/vanguardsh/vanguard/releases/latest' => Http::response([
            'tag_name' => '1.1.0',
            'published_at' => '2024-01-01T00:00:00Z',
        ], 200),
    ]);

    $this->artisan(CheckVersion::class, ['--check' => true])
        ->expectsOutputToContain('There is a new version of Vanguard available.')
        ->expectsOutputToContain('You are using version 1.0.0 and the latest version is 1.1.0 (released on 2024-01-01T00:00:00Z).')
        ->assertExitCode(0);
});

it('fails to check the latest version due to an API error', function () {
    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(true);
    File::shouldReceive('get')->with(base_path('VERSION'))->andReturn('1.0.0');

    Http::fake([
        'https://api.github.com/repos/vanguardsh/vanguard/releases/latest' => Http::response([], 500),
    ]);

    $this->artisan(CheckVersion::class, ['--check' => true])
        ->expectsOutputToContain('Unable to check the latest version of Vanguard. Please try again later.')
        ->assertExitCode(0);
});
