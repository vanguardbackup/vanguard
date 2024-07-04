<?php

declare(strict_types=1);

use App\Actions\BackupDestinations\CheckS3Connection;
use App\Console\Commands\ValidateS3ConnectionCommand;
use App\Models\BackupDestination;

it('displays an error message if the backup destination is not an S3 connection', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => 'local',
    ]);

    $this->artisan(ValidateS3ConnectionCommand::class, ['id' => $backupDestination->id])
        ->expectsOutputToContain('Backup destination is not an S3 connection.')
        ->assertExitCode(0);
});

it('displays a success message if the connection is successful', function () {
    $backupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_S3,
    ]);

    $this->mock(CheckS3Connection::class, function ($mock) {
        $mock->shouldReceive('handle')->andReturn(true);
    });

    $this->artisan(ValidateS3ConnectionCommand::class, ['id' => $backupDestination->id])
        ->expectsOutputToContain('Connection successful.')
        ->assertExitCode(0);
});

it('displays an error message if the connection fails', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_S3,
    ]);

    $this->mock(CheckS3Connection::class, function ($mock) {
        $mock->shouldReceive('handle')->andReturn(false);
    });

    $this->artisan(ValidateS3ConnectionCommand::class, ['id' => $backupDestination->id])
        ->expectsOutputToContain('Connection failed.')
        ->assertExitCode(0);
});

it('displays an error message if the backup destination does not exist', function () {

    $this->artisan(ValidateS3ConnectionCommand::class, ['id' => 14564563])
        ->expectsOutputToContain('The backup destination does not exist.')
        ->assertExitCode(0);

    $this->assertDatabaseCount('backup_destinations', 0);
});
