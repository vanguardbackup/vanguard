<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Artisan command to check and display Vanguard version information.
 *
 * This command allows users to view the current version of Vanguard
 * and check for available updates.
 */
class CheckVersion extends Command
{
    protected $signature = 'vanguard:version {--check}';

    protected $description = 'Check the current version of Vanguard.';

    /**
     * Handle the command execution.
     */
    public function handle(): void
    {
        if ($this->option('check')) {
            $this->checkForNewVersion();

            return;
        }

        $this->showCurrentVersion();
    }

    /**
     * Display the current version of Vanguard.
     */
    protected function showCurrentVersion(): void
    {
        $version = obtain_vanguard_version();

        $this->components->info(sprintf('The current version of Vanguard is: %s.', $version));
    }

    /**
     * Check for a new version of Vanguard and display the result.
     */
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
            $this->components->info(sprintf('You are using version %s and the latest version is %s (released on %s).', $currentVersion, $latestVersion, $publishedAt));
        }
    }

    /**
     * Fetch the latest version information from the GitHub API.
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
