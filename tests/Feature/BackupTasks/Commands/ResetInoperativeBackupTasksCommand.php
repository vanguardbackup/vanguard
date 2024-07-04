<?php

declare(strict_types=1);

use App\Console\Commands\ResetInoperativeBackupTasksCommand;
use App\Models\BackupTask;

it('should not reset tasks that are below the threshold', function () {

    $backupTask = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
        'last_script_update_at' => now()->subSeconds(27 * 60),
    ]);

    $backupTaskTwo = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
        'last_script_update_at' => now()->subSeconds(28 * 60),
    ]);

    $this->artisan(ResetInoperativeBackupTasksCommand::class)
        ->assertExitCode(0);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTask->id,
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTaskTwo->id,
        'status' => BackupTask::STATUS_RUNNING,
    ]);
});

it('should reset tasks that exceed the threshold', function () {

    $backupTask = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
        'last_script_update_at' => now()->subSeconds(31 * 60),
    ]);

    $backupTaskTwo = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
        'last_script_update_at' => now()->subSeconds(35 * 60),
    ]);

    $backupTaskThree = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
        'last_script_update_at' => now()->subSeconds(40 * 60),
    ]);

    $backupTaskFour = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
        'last_script_update_at' => now()->subSeconds(60 * 60),
    ]);

    $backupTaskFive = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
        'last_script_update_at' => now()->subSeconds(12000 * 60),
    ]);

    $this->artisan(ResetInoperativeBackupTasksCommand::class)
        ->assertExitCode(0);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTaskTwo->id,
        'status' => BackupTask::STATUS_READY,
    ]);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTaskThree->id,
        'status' => BackupTask::STATUS_READY,
    ]);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTaskFour->id,
        'status' => BackupTask::STATUS_READY,
    ]);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTaskFive->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

it('should not reset tasks that have a null value in the last_script_update_at column', function () {

    $backupTask = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
        'last_script_update_at' => null,
    ]);

    $this->artisan(ResetInoperativeBackupTasksCommand::class)
        ->assertExitCode(0);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTask->id,
        'status' => BackupTask::STATUS_RUNNING,
    ]);
});

it('should not perform any action when no tasks exist', function () {
    $this->artisan(ResetInoperativeBackupTasksCommand::class)
        ->assertExitCode(0);
});

it('should not perform any action when all tasks are already in the ready state', function () {
    $backupTask = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_READY,
        'last_script_update_at' => now()->subSeconds(16 * 60),
    ]);

    $this->artisan(ResetInoperativeBackupTasksCommand::class)
        ->assertExitCode(0);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

it('should only reset tasks that are in the running state and exceed the threshold', function () {
    $backupTaskRunning = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
        'last_script_update_at' => now()->subSeconds(35 * 60),
    ]);

    $backupTaskReady = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_READY,
        'last_script_update_at' => now()->subSeconds(50 * 60),
    ]);

    $this->artisan(ResetInoperativeBackupTasksCommand::class)
        ->assertExitCode(0);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTaskRunning->id,
        'status' => BackupTask::STATUS_READY,
    ]);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTaskReady->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});
