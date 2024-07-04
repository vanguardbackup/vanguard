<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\LogModal;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Livewire\Livewire;

test('the listener responds to the event correctly', function (): void {
    $task = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    BackupTaskLog::create([
        'backup_task_id' => $task->id,
        'output' => 'Log output from the model',
    ]);

    $logOutput = 'Log output from the event';

    $component = Livewire::test(LogModal::class, ['backupTask' => $task])
        ->call('updateLogOutput', ['logOutput' => $logOutput])
        ->assertSet('logOutput', 'Log output from the event')
        ->assertSet('isWaiting', false);

    $this->assertEquals($component->get('logOutput'), $logOutput);
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
        ->call('refreshSelf')
        ->assertSet('logOutput', 'Some log output');

    $this->assertEquals('Some log output', $task->refresh()->getAttribute('logs')->first()->output);
});

it('renders the component correctly when live log output has been given', function (): void {
    $task = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    BackupTaskLog::create([
        'backup_task_id' => $task->id,
        'output' => 'Some log output',
    ]);

    $component = Livewire::test(LogModal::class, ['backupTask' => $task])
        ->call('updateLogOutput', ['logOutput' => 'Some log output']);

    $component->assertSee('Some log output');
});

it('renders the spinner when the task is running before log output is given', function (): void {

    $task = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    BackupTaskLog::create([
        'backup_task_id' => $task->id,
        'output' => 'Some log output',
    ]);

    $component = Livewire::test(LogModal::class, ['backupTask' => $task]);

    $component->assertSet('isWaiting', true); // Spinner should be visible

    $component->assertSee('Waiting for backup task...');
    $component->assertDontSee('Some log output');
});

it('renders the component correctly when viewing past log history', function (): void {

    $task = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_READY,
    ]);

    BackupTaskLog::create([
        'backup_task_id' => $task->id,
        'output' => 'Some log output',
    ]);

    $component = Livewire::test(LogModal::class, ['backupTask' => $task]);

    $component->assertSet('isWaiting', false); // Spinner should not be visible

    $component->assertSee('Some log output');
});

it('correctly outputs the latest log', function (): void {
    $backupTask = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    $olderLog = BackupTaskLog::factory()->create([
        'output' => 'Older log output',
        'created_at' => now()->subMinutes(5),
        'backup_task_id' => $backupTask->id,
    ]);

    $newLog = BackupTaskLog::factory()->create([
        'output' => 'Latest log output',
        'created_at' => now(),
        'backup_task_id' => $backupTask->id,
    ]);

    $component = Livewire::test(LogModal::class, ['backupTask' => $backupTask]);

    $component->call('updateLogOutput', ['logOutput' => $newLog->output])
        ->assertSet('logOutput', 'Latest log output')
        ->assertSet('isWaiting', false);

    $component->assertSee($newLog->output);
    $component->assertDontSee($olderLog->output);

    $component->assertDispatched('$refresh');
    $component->assertDispatched('log-modal-updated-' . $backupTask->id);
});

it('handles log data to the logOutput variable if the task is running', function (): void {
    $backupTask = BackupTask::factory()->create([
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    BackupTaskLog::factory()->create([
        'output' => 'Some log output',
        'backup_task_id' => $backupTask->id,
    ]);

    $component = Livewire::test(LogModal::class, ['backupTask' => $backupTask]);

    $component->call('updateLogOutput', ['logOutput' => 'The first output']);

    $component->call('updateLogOutput', ['logOutput' => 'The second output']);

    $component->assertSet('logOutput', 'The second output');
    $component->assertNotSet('logOutput', "The first output\nThe second output");
    $component->assertNotSet('logOutput', "'Some log output\nThe second output'");
    $component->assertSet('isWaiting', false);
});
