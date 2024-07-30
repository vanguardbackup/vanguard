<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * Class RefreshApplicationForDeployment
 *
 * This command refreshes the Vanguard application for deployment,
 * performing various optimization and update tasks.
 */
class RefreshApplicationForDeployment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vanguard:refresh
                            {--skip-migrations : Skip running migrations}
                            {--skip-cache : Skip cache operations}
                            {--force : Force the operation to run in debug mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Vanguard application with optimized deployment steps';

    /**
     * The path to the version file.
     */
    private string $versionFile = 'VERSION';

    /**
     * The cache key for storing the application version.
     */
    private string $versionCacheKey = 'vanguard_version';

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        if (! $this->checkDebugMode()) {
            return CommandAlias::FAILURE;
        }

        $startTime = microtime(true);

        $this->components->info('Starting Vanguard refresh process...');

        $this->performRefreshSteps();

        $duration = round(microtime(true) - $startTime, 2);
        $this->components->info("Vanguard refresh process completed successfully in {$duration} seconds.");

        $this->logDeployment($duration);

        return CommandAlias::SUCCESS;
    }

    /**
     * Check if the application is in debug mode and handle accordingly.
     */
    private function checkDebugMode(): bool
    {
        if (! config('app.debug')) {
            return true;
        }

        $this->components->error('WARNING: Application is in debug mode!');
        $this->components->warn('It is not recommended to run deployments with debug mode enabled.');

        if ($this->option('force')) {
            return $this->components->confirm('Are you sure you want to continue?');
        }

        $this->components->info('Use --force to override this check and proceed anyway.');

        return false;
    }

    /**
     * Perform all refresh steps in sequence.
     */
    private function performRefreshSteps(): void
    {
        $steps = [
            'checkAndClearVersionCache',
            'terminateHorizon',
            'restartServices',
            'cacheApplication',
            'runMigrations',
            'fetchNewFeatures',
            'optimizeApplication',
        ];

        foreach ($steps as $step) {
            $this->$step();
        }
    }

    /**
     * Check and clear the version cache if necessary.
     */
    private function checkAndClearVersionCache(): void
    {
        $this->components->task('Checking version cache', function () {
            $currentVersion = $this->getCurrentVersion();
            $cachedVersion = Cache::get($this->versionCacheKey);

            if (! $currentVersion) {
                return 'Version file not found or empty';
            }

            if (! $cachedVersion) {
                return 'Cache not set';
            }

            if ($currentVersion === $cachedVersion) {
                return 'No change';
            }

            Cache::forget($this->versionCacheKey);

            return true;
        });
    }

    /**
     * Get the current version from the VERSION file.
     *
     * @throws FileNotFoundException
     */
    private function getCurrentVersion(): ?string
    {
        $path = base_path($this->versionFile);

        if (! File::exists($path)) {
            $this->components->warn("Version file not found: {$this->versionFile}");

            return null;
        }

        $version = trim(File::get($path));

        if (empty($version)) {
            $this->components->warn("Version file is empty: {$this->versionFile}");

            return null;
        }

        return $version;
    }

    /**
     * Terminate the Horizon process.
     */
    private function terminateHorizon(): void
    {
        $this->runArtisanCommand('Terminating Horizon', 'horizon:terminate');
    }

    /**
     * Restart Reverb and Pulse services.
     */
    private function restartServices(): void
    {
        $this->runArtisanCommand('Restarting Reverb', 'reverb:restart');
        $this->runArtisanCommand('Restarting Pulse', 'pulse:restart');
    }

    /**
     * Cache application configuration, routes, and views if not skipped.
     */
    private function cacheApplication(): void
    {
        if ($this->option('skip-cache')) {
            $this->components->info('Skipping cache operations.');

            return;
        }

        $this->runArtisanCommand('Caching configuration', 'config:cache');
        $this->runArtisanCommand('Caching routes', 'route:cache');
        $this->runArtisanCommand('Caching views', 'view:cache');
    }

    /**
     * Run database migrations if not skipped.
     */
    private function runMigrations(): void
    {
        if ($this->option('skip-migrations')) {
            $this->components->info('Skipping migrations.');

            return;
        }

        $this->runArtisanCommand('Running migrations', 'migrate', ['--force' => true]);
    }

    /**
     * Fetch new features for the application.
     */
    private function fetchNewFeatures(): void
    {
        $this->runArtisanCommand('Fetching new features', 'vanguard:fetch-new-features');
    }

    /**
     * Optimize the application.
     */
    private function optimizeApplication(): void
    {
        $this->runArtisanCommand('Optimizing application', 'optimize');
    }

    /**
     * Run an Artisan command and handle its output.
     *
     * @param string $description The description of the task
     * @param string $command The Artisan command to run
     * @param array<string, mixed> $parameters The parameters for the Artisan command
     */
    private function runArtisanCommand(string $description, string $command, array $parameters = []): void
    {
        $this->components->task($description, function () use ($command, $parameters) {
            try {
                $this->info("Running Artisan command: {$command}");
                Artisan::call($command, $parameters);

                return true;
            } catch (Exception $e) {
                $this->components->error("Error in {$command}: " . $e->getMessage());

                return false;
            }
        });
    }

    /**
     * Log the deployment details.
     *
     * @throws FileNotFoundException
     */
    private function logDeployment(float $duration): void
    {
        $version = $this->getCurrentVersion() ?? 'Unknown';
        Log::info("Deployment completed. Version: {$version}, Duration: {$duration} seconds");
    }
}
