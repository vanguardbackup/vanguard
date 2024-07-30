<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * Command to fetch new features from GitHub or a local file and store them in cache.
 */
class FetchNewFeatures extends Command
{
    protected $signature = 'vanguard:fetch-new-features {--local : Fetch from local new_features.json file}';

    protected $description = 'Fetch new features from GitHub or local file and store in cache';

    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        try {
            $features = $this->fetchFeatures();

            if ($features === []) {
                $this->error('No new features found.');

                return CommandAlias::FAILURE;
            }

            $this->storeLatestFeature($features);
            $this->info('New features fetched and stored successfully.');

            return CommandAlias::SUCCESS;
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return CommandAlias::FAILURE;
        }
    }

    /**
     * Fetch features from either local or remote source.
     *
     * @return array<int, array<string, string>>
     */
    private function fetchFeatures(): array
    {
        return $this->option('local') ? $this->fetchLocal() : $this->fetchRemote();
    }

    /**
     * Fetch features from the remote GitHub repository.
     *
     * @return array<int, array<string, string>>
     *
     * @throws Exception
     */
    private function fetchRemote(): array
    {
        try {
            $response = Http::get('https://raw.githubusercontent.com/vanguardbackup/vanguard/main/new_features.json');
            $response->throw();

            return $response->json();
        } catch (RequestException $e) {
            throw new Exception("Failed to fetch new features from remote: {$e->getMessage()}", $e->getCode(), $e);
        }
    }

    /**
     * Fetch features from the local file.
     *
     * @return array<int, array<string, string>>
     *
     * @throws Exception
     */
    private function fetchLocal(): array
    {
        $path = base_path('new_features.json');

        if (! File::exists($path)) {
            throw new Exception('Local new_features.json file not found in project root.');
        }

        $content = File::get($path);
        $features = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($features)) {
            throw new Exception('Failed to parse local new_features.json file.');
        }

        return $features;
    }

    /**
     * Store the latest feature in the cache if available.
     *
     * @param  array<int, array<string, string>>  $features
     *
     * @throws FileNotFoundException
     */
    private function storeLatestFeature(array $features): void
    {
        if ($features === []) {
            $this->info('No features to store.');

            return;
        }

        $latestFeature = end($features);
        if ($latestFeature === false) {
            $this->error('Failed to retrieve the latest feature.');

            return;
        }

        $latestFeature['current_version'] = $this->getCurrentVersion();
        $latestFeature['github_url'] ??= 'https://github.com/vanguardbackup/vanguard';

        Cache::put('latest_feature', $latestFeature, now()->addDay());
    }

    /**
     * Get the current version from the VERSION file.
     *
     * @throws FileNotFoundException
     */
    private function getCurrentVersion(): string
    {
        $versionFile = base_path('VERSION');

        return File::exists($versionFile) ? trim(File::get($versionFile)) : '0.0.0';
    }
}
