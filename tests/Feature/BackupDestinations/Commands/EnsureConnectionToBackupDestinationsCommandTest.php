<?php

declare(strict_types=1);

use App\Console\Commands\EnsureConnectionToBackupDestinationsCommand;
use App\Jobs\CheckBackupDestinationsS3ConnectionJob;
use App\Models\BackupDestination;

it('dispatches a batch to check the connection of eligible backup destinations', function (): void {
    Bus::fake();

    $S3BackupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_S3,
    ]);

    $CustomS3BackupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_CUSTOM_S3,
    ]);

    $localBackupDestination = BackupDestination::factory()->create([
        'type' => 'local',
    ]);

    $this->artisan(EnsureConnectionToBackupDestinationsCommand::class)
        ->expectsOutputToContain('Checking connection to eligible backup destinations...')
        ->assertExitCode(0);

    Bus::assertBatched(function ($batch) use ($S3BackupDestination, $CustomS3BackupDestination, $localBackupDestination): bool {
        $jobs = $batch->jobs;

        return $batch->name === 'Check connection to eligible backup destinations'
            && $jobs->count() === 2
            && $jobs->contains(function ($job) use ($S3BackupDestination): bool {
                return $job instanceof CheckBackupDestinationsS3ConnectionJob
                    && $job->backupDestination->is($S3BackupDestination);
            })
            && $jobs->contains(function ($job) use ($CustomS3BackupDestination): bool {
                return $job instanceof CheckBackupDestinationsS3ConnectionJob
                    && $job->backupDestination->is($CustomS3BackupDestination);
            })
            && ! $jobs->contains(function ($job) use ($localBackupDestination): bool {
                return $job instanceof CheckBackupDestinationsS3ConnectionJob
                    && $job->backupDestination->is($localBackupDestination);
            });
    });
});

it('does not dispatch a batch if no backup destinations are found', function (): void {
    Bus::fake();

    $this->artisan(EnsureConnectionToBackupDestinationsCommand::class)
        ->expectsOutputToContain('Checking connection to eligible backup destinations...')
        ->expectsOutputToContain('No backup destinations found.')
        ->assertExitCode(0);

    Bus::assertNotDispatched(CheckBackupDestinationsS3ConnectionJob::class);
});

it('does not dispatch a batch if no eligible backup destinations are found', function (): void {
    Bus::fake();

    BackupDestination::factory()->create([
        'type' => 'local',
    ]);

    $this->artisan(EnsureConnectionToBackupDestinationsCommand::class)
        ->expectsOutputToContain('Checking connection to eligible backup destinations...')
        ->expectsOutputToContain('No eligible backup destinations found.')
        ->assertExitCode(0);

    Bus::assertNotDispatched(CheckBackupDestinationsS3ConnectionJob::class);
});
