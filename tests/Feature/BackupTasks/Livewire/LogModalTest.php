<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\Modals\LogModal;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Livewire\Livewire;

test('the listener responds to the event correctly', function (): void {
    $task = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    $logOutput = 'Log output from the event';

    Livewire::test(LogModal::class, ['backupTask' => $task])
        ->call('handleStreamEvent', ['logOutput' => $logOutput])
        ->assertSet('logOutput', $logOutput)
        ->assertSet('isStreaming', true)
        ->assertSet('isLoading', false);
});

it('refreshes itself when the method is called', function (): void {
    $task = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_READY,
    ]);

    BackupTaskLog::create([
        'backup_task_id' => $task->id,
        'output' => 'Some log output',
    ]);

    Livewire::test(LogModal::class, ['backupTask' => $task])
        ->call('refresh')
        ->assertSet('logOutput', 'Some log output')
        ->assertSet('isStreaming', false)
        ->assertSet('isLoading', false);
});

it('updates component state when live log output is given', function (): void {
    $task = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    Livewire::test(LogModal::class, ['backupTask' => $task])
        ->call('handleStreamEvent', ['logOutput' => 'Some log output'])
        ->assertSet('logOutput', 'Some log output')
        ->assertSet('isStreaming', true)
        ->assertSet('isLoading', false);
});

it('sets streaming state when the task is running', function (): void {
    $task = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    Livewire::test(LogModal::class, ['backupTask' => $task])
        ->assertSet('isLoading', false)
        ->assertSet('isStreaming', true)
        ->assertSet('logOutput', 'No log output available.');
});

it('correctly sets component state when viewing past log history', function (): void {
    $task = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_READY,
    ]);

    BackupTaskLog::create([
        'backup_task_id' => $task->id,
        'output' => 'Some log output',
    ]);

    Livewire::test(LogModal::class, ['backupTask' => $task])
        ->assertSet('isLoading', false)
        ->assertSet('isStreaming', false)
        ->assertSet('logOutput', 'Some log output');
});

it('correctly outputs the latest log', function (): void {
    $backupTask = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    BackupTaskLog::factory()->create([
        'output' => 'Older log output',
        'created_at' => now()->subMinutes(5),
        'backup_task_id' => $backupTask->id,
    ]);

    $newLog = BackupTaskLog::factory()->create([
        'output' => 'Latest log output',
        'created_at' => now(),
        'backup_task_id' => $backupTask->id,
    ]);

    Livewire::test(LogModal::class, ['backupTask' => $backupTask])
        ->assertSet('logOutput', 'Latest log output')
        ->assertSet('isStreaming', true)
        ->assertSet('isLoading', false);
});

it('updates logOutput with the latest data when the task is running', function (): void {
    $backupTask = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    $component = Livewire::test(LogModal::class, ['backupTask' => $backupTask]);

    $component->call('handleStreamEvent', ['logOutput' => 'The first output'])
        ->assertSet('logOutput', 'The first output');

    $component->call('handleStreamEvent', ['logOutput' => 'The second output'])
        ->assertSet('logOutput', 'The second output')
        ->assertSet('isStreaming', true)
        ->assertSet('isLoading', false);
});
