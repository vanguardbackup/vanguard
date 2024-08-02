<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\Buttons\TogglePauseButton;
use App\Models\BackupTask;
use Livewire\Livewire;

it('refreshes component when listener method is called', function (): void {
    $backupTask = BackupTask::factory()->create();

    Livewire::test(TogglePauseButton::class, ['backupTask' => $backupTask])
        ->call('refreshSelf')
        ->assertDispatched('$refresh');
});

it('resumes task and dispatches event when paused', function (): void {
    Toaster::fake();
    $backupTask = BackupTask::factory()->paused()->create();

    Livewire::test(TogglePauseButton::class, ['backupTask' => $backupTask])
        ->call('togglePauseState')
        ->assertDispatched('toggle-pause-button-clicked-' . $backupTask->id);

    expect($backupTask->refresh()->isPaused())->toBeFalse();

    Toaster::assertDispatched(__('Backup task has been resumed.'));
});

it('pauses task and dispatches event when not paused', function (): void {
    Toaster::fake();
    $backupTask = BackupTask::factory()->create();

    Livewire::test(TogglePauseButton::class, ['backupTask' => $backupTask])
        ->call('togglePauseState')
        ->assertDispatched('toggle-pause-button-clicked-' . $backupTask->id);

    expect($backupTask->refresh()->isPaused())->toBeTrue();

    Toaster::assertDispatched(__('Backup task has been paused.'));
});

it('renders the component view', function (): void {
    $backupTask = BackupTask::factory()->create();

    Livewire::test(TogglePauseButton::class, ['backupTask' => $backupTask])
        ->assertViewIs('livewire.backup-tasks.buttons.toggle-pause-button');
});

it('rate limits the button if pressed too much', function (): void {
    Toaster::fake();

    $backupTask = BackupTask::factory()->paused()->create();

    Livewire::test(TogglePauseButton::class, ['backupTask' => $backupTask])
        ->call('togglePauseState')
        ->call('togglePauseState')
        ->call('togglePauseState')
        ->call('togglePauseState');

    Toaster::assertDispatched('You are doing this too often.');
});
