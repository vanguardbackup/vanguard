<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\RunTaskButton;
use App\Models\BackupTask;

it('renders the component view', function () {
    $backupTask = BackupTask::factory()->create();

    Livewire::test(RunTaskButton::class, ['backupTask' => $backupTask])
        ->assertViewIs('livewire.backup-tasks.run-task-button');
});

it('refreshes component when listener is called', function () {
    $backupTask = BackupTask::factory()->create();

    Livewire::test(RunTaskButton::class, ['backupTask' => $backupTask])
        ->call('refreshSelf')
        ->assertDispatched('$refresh');
});

it('runs task and dispatches event', function () {
    Queue::fake();
    Toaster::fake();

    $backupTask = BackupTask::factory()->create();

    Livewire::test(RunTaskButton::class, ['backupTask' => $backupTask])
        ->call('runTask')
        ->assertDispatched('task-button-clicked-' . $backupTask->id);

    expect($backupTask->refresh()->getAttribute('status'))->toBe(BackupTask::STATUS_RUNNING);

    Toaster::assertDispatched(__('Backup task is running.'));
});
