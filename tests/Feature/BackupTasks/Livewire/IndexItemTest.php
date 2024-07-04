<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\IndexItem;
use App\Models\BackupTask;
use Livewire\Livewire;

it('dispatches refresh and update events on task status update', function (): void {
    $backupTask = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    $component = Livewire::test(IndexItem::class, ['backupTask' => $backupTask]);

    $component->call('echoBackupTaskStatusReceivedEvent');

    $component->assertDispatched('$refresh');
    $component->assertDispatched('update-run-button-' . $backupTask->id);
});

it('updates log information on log creation event', function (): void {
    $backupTask = BackupTask::factory()->create();

    $oldLog = $backupTask->logs()->create([
        'output' => 'Old log message',
    ]);

    $log = $backupTask->logs()->create([
        'output' => 'New log message',
    ]);

    $component = Livewire::test(IndexItem::class, ['backupTask' => $backupTask]);

    $component->call('echoBackupTaskLogCreatedEvent', [
        'logId' => $log->id,
    ]);

    $component->assertDispatched('backup-task-item-updated-' . $backupTask->id);

    $backupTask->refresh();

    $log->refresh();

    $component->assertSet('backupTaskLog.id', $log->id);
    $component->assertSet('backupTaskLog.output', 'New log message');
    expect($component->backupTaskLog->id)->toBe($log->id)
        ->and($component->backupTaskLog->output)->toBe('New log message')
        ->and($component->backupTaskLog->id)->toBe($log->id);
});

it('dispatches item updated event on log creation', function (): void {
    $backupTask = BackupTask::factory()->create();

    $component = Livewire::test(IndexItem::class, ['backupTask' => $backupTask]);

    $backupTaskLog = $backupTask->logs()->create([
        'output' => 'New log message',
    ]);

    $component->call('echoBackupTaskLogCreatedEvent', [
        'logId' => $backupTaskLog->id,
    ]);

    $component->assertDispatched('backup-task-item-updated-' . $backupTask->id);
});
