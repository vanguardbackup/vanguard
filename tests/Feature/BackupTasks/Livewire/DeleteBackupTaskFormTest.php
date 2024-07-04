<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\DeleteBackupTaskForm;
use App\Models\BackupTask;
use App\Models\User;

test('the component can be rendered', function (): void {

    Livewire::test(DeleteBackupTaskForm::class, ['backupTask' => BackupTask::factory()->create()])
        ->assertStatus(200);
});

test('a backup task can be deleted by its creator', function (): void {

    $user = User::factory()->create();
    $backupTask = BackupTask::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(DeleteBackupTaskForm::class, ['backupTask' => $backupTask])
        ->call('delete');

    $this->assertDatabaseMissing('backup_tasks', ['id' => $backupTask->id]);
    $this->assertAuthenticatedAs($user);
});

test('a backup task cannot be deleted by another user', function (): void {

    $user = User::factory()->create();
    $backupTask = BackupTask::factory()->create();

    $this->actingAs($user);

    Livewire::test(DeleteBackupTaskForm::class, ['backupTask' => BackupTask::factory()->create()])
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('backup_tasks', ['id' => $backupTask->id]);
    $this->assertAuthenticatedAs($user);
});
