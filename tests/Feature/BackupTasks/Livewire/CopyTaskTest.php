<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\Modals\CopyTaskModal;
use App\Models\BackupTask;
use App\Models\NotificationStream;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->backupTask = BackupTask::factory()->create([
        'user_id' => $this->user->id,
        'frequency' => 'daily',
        'time_to_run_at' => '00:00',
    ]);
});

test('the component can be rendered', function (): void {
    $this->actingAs($this->user);

    Livewire::test(CopyTaskModal::class)
        ->assertOk();
});

test('it loads user backup tasks', function (): void {
    $this->actingAs($this->user);

    Livewire::test(CopyTaskModal::class)
        ->assertViewHas('backupTasks', fn ($backupTasks): bool => $backupTasks->count() === 1 && $backupTasks->first()->id === $this->backupTask->id
        );
});

test('it updates frequency and time when task is selected', function (): void {
    $this->actingAs($this->user);

    Livewire::test(CopyTaskModal::class)
        ->set('backupTaskToCopyId', $this->backupTask->id)
        ->assertSet('frequency', $this->backupTask->frequency)
        ->assertSet('timeToRun', $this->backupTask->time_to_run_at);
});

test('it validates required fields', function (): void {
    $this->actingAs($this->user);

    Livewire::test(CopyTaskModal::class)
        ->set('backupTaskToCopyId', $this->backupTask->id)
        ->call('copyTask')
        ->assertHasErrors(['timeToRun']);
});

test('it validates frequency options', function (): void {
    $this->actingAs($this->user);

    Livewire::test(CopyTaskModal::class)
        ->set('backupTaskToCopyId', $this->backupTask->id)
        ->set('frequency', 'invalid')
        ->call('copyTask')
        ->assertHasErrors(['frequency']);
});

test('it validates time format', function (): void {
    $this->actingAs($this->user);

    Livewire::test(CopyTaskModal::class)
        ->set('backupTaskToCopyId', $this->backupTask->id)
        ->set('timeToRun', 'invalid')
        ->call('copyTask')
        ->assertHasErrors(['timeToRun']);
});

test('it successfully copies a task', function (): void {
    $this->actingAs($this->user);

    $newLabel = 'New Copied Task';

    Livewire::test(CopyTaskModal::class)
        ->set('backupTaskToCopyId', $this->backupTask->id)
        ->set('optionalNewLabel', $newLabel)
        ->set('frequency', 'weekly')
        ->set('timeToRun', '12:00')
        ->call('copyTask')
        ->assertHasNoErrors()
        ->assertDispatched('task-copied')
        ->assertDispatched('close-modal')
        ->assertDispatched('refreshBackupTasksTable');

    $this->assertDatabaseHas('backup_tasks', [
        'label' => $newLabel,
        'frequency' => 'weekly',
        'time_to_run_at' => '12:00',
    ]);
});

test('it copies task relationships', function (): void {
    $this->actingAs($this->user);

    $tag = $this->backupTask->tags()->create(['label' => 'Test Tag', 'user_id' => $this->user->id]);
    $stream = $this->backupTask->notificationStreams()->create(['label' => 'Test Stream', 'user_id' => $this->user->id, 'type' => NotificationStream::TYPE_EMAIL, 'value' => 'email@email.com']);

    Livewire::test(CopyTaskModal::class)
        ->set('backupTaskToCopyId', $this->backupTask->id)
        ->set('frequency', 'daily')
        ->set('timeToRun', '00:00')
        ->call('copyTask');

    $newTask = BackupTask::latest()->first();

    expect($newTask->tags)->toHaveCount(1)
        ->and($newTask->tags->first()->id)->toBe($tag->id)
        ->and($newTask->notificationStreams)->toHaveCount(1)
        ->and($newTask->notificationStreams->first()->id)->toBe($stream->id);
});

test('it resets form after copying', function (): void {
    $this->actingAs($this->user);

    Livewire::test(CopyTaskModal::class)
        ->set('backupTaskToCopyId', $this->backupTask->id)
        ->set('optionalNewLabel', 'Test Label')
        ->set('frequency', 'weekly')
        ->set('timeToRun', '12:00')
        ->call('copyTask')
        ->assertSet('backupTaskToCopyId', null)
        ->assertSet('optionalNewLabel', null)
        ->assertSet('frequency', 'daily')
        ->assertSet('timeToRun', '00:00');
});
