<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class CheckVersion extends Command
{
    protected $signature = 'vanguard:version {--check}';
    protected $description = 'Check the current version of Vanguard.';

    public function handle(): void
    {
        if ($this->option('check')) {
            $this->checkForNewVersion();

            return;
        }

        $this->showCurrentVersion();
    }

    protected function showCurrentVersion(): void
    {
        $version = $this->getCurrentVersion();

        if ($version === null) {
            $this->components->error('Unable to determine the current version. The version file is missing.');

            return;
        }

        $this->components->info("The current version of Vanguard is: {$version}.");
    }

    protected function checkForNewVersion(): void
    {
        $latestVersionInfo = $this->getLatestVersion();

        if ($latestVersionInfo === null) {
            $this->components->error('Unable to check the latest version of Vanguard. Please try again later.');

            return;
        }

        $currentVersion = $this->getCurrentVersion();

        if ($currentVersion === null) {
            $this->components->error('Unable to determine the current version. The version file is missing.');

            return;
        }

        $latestVersion = $latestVersionInfo['tag_name'];
        $publishedAt = $latestVersionInfo['published_at'];

        if ($currentVersion === $latestVersion) {
            $this->components->info('You are using the latest version of Vanguard.');
        } else {
            $this->components->warn('There is a new version of Vanguard available.');
            $this->components->info("You are using version {$currentVersion} and the latest version is {$latestVersion} (released on {$publishedAt}).");
        }
    }

    protected function getCurrentVersion(): ?string
    {
        $versionFile = base_path('VERSION');

        if (! File::exists($versionFile)) {
            return null;
        }

        return str_replace("\n", '', File::get($versionFile));
    }

    protected function getLatestVersion(): ?array
    {
        $url = 'https://api.github.com/repos/vanguardsh/vanguard/releases/latest';

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Laravel-App',
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'tag_name' => $data['tag_name'] ?? null,
                    'published_at' => $data['published_at'] ?? null,
                ];
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
