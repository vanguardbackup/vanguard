<?php

use App\Console\Commands\CheckVersion;
use Illuminate\Support\Facades\File;

it('fails if it cannot find the version file', function () {
    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(false);

    $this->artisan(CheckVersion::class)
        ->expectsOutputToContain('The version file is missing.')
        ->assertExitCode(0);
});

it('returns the current version number from the file', function () {
    File::shouldReceive('exists')->with(base_path('VERSION'))->andReturn(true);
    File::shouldReceive('get')->with(base_path('VERSION'))->andReturn('1.0.0');

    $this->artisan(CheckVersion::class)
        ->expectsOutputToContain('The current version of Vanguard is: 1.0.0.')
        ->assertExitCode(0);
});
