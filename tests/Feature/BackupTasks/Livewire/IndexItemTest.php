<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\Tables\IndexItem;
use App\Models\BackupTask;
use Livewire\Livewire;

it('dispatches refresh and update events on task status update', function (): void {
    $backupTask = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    $testable = Livewire::test(IndexItem::class, ['backupTask' => $backupTask]);

    $testable->call('echoBackupTaskStatusReceivedEvent');

    $testable->assertDispatched('$refresh');
    $testable->assertDispatched('update-run-button-' . $backupTask->id);
});

it('updates log information on log creation event', function (): void {
    $backupTask = BackupTask::factory()->create();

    $oldLog = $backupTask->logs()->create([
        'output' => 'Old log message',
    ]);

    $newLog = $backupTask->logs()->create([
        'output' => 'New log message',
    ]);

    $testable = Livewire::test(IndexItem::class, ['backupTask' => $backupTask]);

    $testable->call('echoBackupTaskLogCreatedEvent', [
        'logId' => $newLog->id,
    ]);

    $testable->assertDispatched('backup-task-item-updated-' . $backupTask->id);

    $backupTask->refresh();
    $newLog->refresh();

    $testable->assertSet('backupTaskLog.id', $newLog->id);
    $testable->assertSet('backupTaskLog.output', 'New log message');

    expect($testable->get('backupTaskLog')->id)->toBe($newLog->id)
        ->and($testable->get('backupTaskLog')->output)->toBe('New log message')
        ->and($testable->get('backupTaskLog')->id)->not->toBe($oldLog->id);
});

it('dispatches item updated event on log creation', function (): void {
    $backupTask = BackupTask::factory()->create();

    $testable = Livewire::test(IndexItem::class, ['backupTask' => $backupTask]);

    $backupTaskLog = $backupTask->logs()->create([
        'output' => 'New log message',
    ]);

    $testable->call('echoBackupTaskLogCreatedEvent', [
        'logId' => $backupTaskLog->id,
    ]);

    $testable->assertDispatched('backup-task-item-updated-' . $backupTask->id);
});
