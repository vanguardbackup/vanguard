<?php

use App\Console\Commands\EnsureConnectionToBackupDestinationsCommand;
use App\Console\Commands\ExecuteScheduledBackupTasksCommand;
use App\Console\Commands\FetchNewFeatures;
use App\Console\Commands\NotifyUsersAboutOldBackupCodes;
use App\Console\Commands\ResetInoperativeBackupTasksCommand;
use App\Console\Commands\ResetQuietModeStatus;
use App\Console\Commands\SendPersonalAccessTokenExpiringSoon;
use App\Console\Commands\SendSummaryBackupTaskEmails;
use App\Console\Commands\VerifyConnectionToRemoteServersCommand;
use Laravel\Sanctum\Console\Commands\PruneExpired;

Schedule::command(ExecuteScheduledBackupTasksCommand::class)
    ->everyMinute();

Schedule::command(VerifyConnectionToRemoteServersCommand::class)
    ->everySixHours();

Schedule::command(EnsureConnectionToBackupDestinationsCommand::class)
    ->twiceDaily(2, 14)->everySixHours();

Schedule::command(ResetInoperativeBackupTasksCommand::class)
    ->everyMinute();

Schedule::command(SendSummaryBackupTaskEmails::class)
    ->mondays()->at('07:00');

Schedule::command(FetchNewFeatures::class)
    ->dailyAt('02:00');

Schedule::command(PruneExpired::class, ['hours' => 24])
    ->dailyAt('05:00');

Schedule::command(SendPersonalAccessTokenExpiringSoon::class)
    ->dailyAt('09:00');

Schedule::command(NotifyUsersAboutOldBackupCodes::class)
    ->monthly();

Schedule::command(ResetQuietModeStatus::class)
    ->dailyAt('00:00');
