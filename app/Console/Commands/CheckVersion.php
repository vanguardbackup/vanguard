<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
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
        $version = obtain_vanguard_version();

        $this->components->info("The current version of Vanguard is: {$version}.");
    }

    protected function checkForNewVersion(): void
    {
        $latestVersionInfo = $this->getLatestVersion();

        if ($latestVersionInfo === null) {
            $this->components->error('Unable to check the latest version of Vanguard. Please try again later.');

            return;
        }

        $currentVersion = obtain_vanguard_version();

        $latestVersion = $latestVersionInfo['tag_name'];
        $publishedAt = $latestVersionInfo['published_at'];

        if ($currentVersion === $latestVersion) {
            $this->components->info('You are using the latest version of Vanguard.');
        } else {
            $this->components->warn('There is a new version of Vanguard available.');
            $this->components->info("You are using version {$currentVersion} and the latest version is {$latestVersion} (released on {$publishedAt}).");
        }
    }

    /**
     * Get the latest version from the GitHub API.
     *
     * @return array<string, string|null>|null
     */
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
        } catch (Exception) {
            return null;
        }
    }
}
