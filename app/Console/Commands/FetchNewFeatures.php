<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * Fetches new features from GitHub or a local file and stores them in cache.
 *
 * This command retrieves feature information either from a remote GitHub repository
 * or a local JSON file, validates the data, and stores the latest valid feature
 * in the application's cache for a week.
 */
class FetchNewFeatures extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'vanguard:fetch-new-features {--local : Fetch from local new_features.json file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch new features from GitHub or local file and store in cache';

    /**
     * Execute the console command.
     *
     * This method orchestrates the feature fetching process, including fetching,
     * validating, and storing the latest feature if it's new.
     *
     * @return int The command exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        try {
            $features = $this->fetchFeatures();

            if ($features === []) {
                $this->components->error('No new features found.');

                return CommandAlias::SUCCESS;
            }

            $latestFeature = $this->getLatestValidFeature($features);

            if ($latestFeature === null) {
                $this->components->error('No valid features found.');

                return CommandAlias::SUCCESS;
            }

            if (! $this->isNewFeature($latestFeature)) {
                $this->components->error('No new features to store.');

                return CommandAlias::SUCCESS;
            }

            $this->storeLatestFeature($latestFeature);
            $this->components->info('New features fetched and stored successfully.');

            return CommandAlias::SUCCESS;
        } catch (Exception $e) {
            $this->error($e->getMessage());
            Log::error('Failed to fetch new features: ' . $e->getMessage());

            return CommandAlias::FAILURE;
        }
    }

    /**
     * Fetch features from either remote or local source based on the --local option.
     *
     * @return array<int, array<string, string>> An array of feature data
     *
     * @throws Exception If fetching or parsing fails
     */
    private function fetchFeatures(): array
    {
        return $this->option('local') ? $this->fetchLocal() : $this->fetchRemote();
    }

    /**
     * Fetch features from the remote GitHub repository.
     *
     * @return array<int, array<string, string>> An array of feature data
     *
     * @throws Exception If the HTTP request fails or returns an unsuccessful status
     */
    private function fetchRemote(): array
    {
        $response = Http::get('https://raw.githubusercontent.com/vanguardbackup/vanguard/main/new_features.json');

        if (! $response->successful()) {
            throw new Exception("Failed to fetch new features from remote: HTTP request returned status code {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Fetch features from the local new_features.json file.
     *
     * @return array<int, array<string, string>> An array of feature data
     *
     * @throws Exception If the file is not found or cannot be parsed
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
     * Get the latest valid feature from the fetched features.
     *
     * @param  array<int, array<string, string>>  $features  The array of fetched features
     * @return array<string, string>|null The latest valid feature or null if none found
     */
    private function getLatestValidFeature(array $features): ?array
    {
        $validFeatures = array_filter($features, [$this, 'isValidFeature']);

        return end($validFeatures) ?: null;
    }

    /**
     * Check if a feature is valid (has all required fields).
     *
     * @param  array<string, string>  $feature  The feature to validate
     * @return bool True if the feature is valid, false otherwise
     */
    private function isValidFeature(array $feature): bool
    {
        return isset($feature['title'], $feature['description'], $feature['version']);
    }

    /**
     * Check if the given feature is newer than the currently cached feature.
     *
     * @param  array<string, string>  $feature  The feature to check
     * @return bool True if the feature is new, false otherwise
     */
    private function isNewFeature(array $feature): bool
    {
        $cachedFeature = Cache::get('latest_feature');

        return ! $cachedFeature || version_compare($feature['version'], $cachedFeature['version'], '>');
    }

    /**
     * Store the latest feature in the cache.
     *
     * This method adds the current application version to the feature data
     * and ensures a GitHub URL is present before caching.
     *
     * @param  array<string, string>  $latestFeature  The feature to store
     */
    private function storeLatestFeature(array $latestFeature): void
    {
        $latestFeature['current_version'] = $this->getCurrentVersion();
        $latestFeature['github_url'] ??= 'https://github.com/vanguardbackup/vanguard';

        Cache::put('latest_feature', $latestFeature, now()->addWeek());
    }

    /**
     * Get the current version of the application.
     *
     * This method reads the version from a VERSION file in the project root.
     * If the file doesn't exist, it returns '0.0.0' as a default version.
     *
     * @return string The current version or '0.0.0' if not found
     * @throws FileNotFoundException
     */
    private function getCurrentVersion(): string
    {
        $versionFile = base_path('VERSION');

        return File::exists($versionFile) ? trim(File::get($versionFile)) : '0.0.0';
    }
}
