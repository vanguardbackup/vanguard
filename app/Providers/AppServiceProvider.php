<?php

declare(strict_types=1);

namespace App\Providers;

use Override;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\GreetingService;
use App\Spotlight\NavigateToAPITokens;
use App\Spotlight\NavigateToAuditLogs;
use App\Spotlight\NavigateToBackupDestinations;
use App\Spotlight\NavigateToBackupTasks;
use App\Spotlight\NavigateToConnections;
use App\Spotlight\NavigateToExperiments;
use App\Spotlight\NavigateToHelp;
use App\Spotlight\NavigateToNotificationStreams;
use App\Spotlight\NavigateToOverview;
use App\Spotlight\NavigateToQuietMode;
use App\Spotlight\NavigateToRemoteServers;
use App\Spotlight\NavigateToSessions;
use App\Spotlight\NavigateToStatistics;
use App\Spotlight\NavigateToTags;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use LivewireUI\Spotlight\Spotlight;

/**
 * Core application service provider.
 * Handles service registration, authorization setup, and feature flags.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    #[Override]
    public function register(): void
    {
        $this->registerGreetingService();
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->defineGates();

        Spotlight::registerCommand(NavigateToBackupTasks::class);
        Spotlight::registerCommand(NavigateToBackupDestinations::class);
        Spotlight::registerCommand(NavigateToRemoteServers::class);
        Spotlight::registerCommand(NavigateToOverview::class);
        Spotlight::registerCommand(NavigateToNotificationStreams::class);
        Spotlight::registerCommand(NavigateToTags::class);
        Spotlight::registerCommand(NavigateToAPITokens::class);
        Spotlight::registerCommand(NavigateToConnections::class);
        Spotlight::registerCommand(NavigateToSessions::class);
        Spotlight::registerCommand(NavigateToExperiments::class);
        Spotlight::registerCommand(NavigateToAuditLogs::class);
        Spotlight::registerCommand(NavigateToHelp::class);
        Spotlight::registerCommand(NavigateToQuietMode::class);
        Spotlight::registerCommand(NavigateToStatistics::class);
    }

    /**
     * Register the GreetingService as a singleton.
     */
    private function registerGreetingService(): void
    {
        $this->app->singleton(GreetingService::class);
        $this->app->alias(GreetingService::class, 'Greeting');
    }

    /**
     * Define application authorization gates.
     */
    private function defineGates(): void
    {
        Gate::define('viewPulse', fn (User $user): bool => $user->isAdmin());
    }
}
