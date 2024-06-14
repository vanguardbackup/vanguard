<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckVersion extends Command
{
    protected $signature = 'vanguard:version';

    protected $description = 'Check the current version of Vanguard.';

    public function handle(): void
    {

        $versionFile = base_path('VERSION');

        if (! File::exists($versionFile)) {
            $this->components->error('Unable to determine the current version. The version file is missing.');

            return;
        }

        $version = str_replace("\n", '', File::get($versionFile));

        $this->components->info("The current version of Vanguard is: {$version}.");
    }
}
