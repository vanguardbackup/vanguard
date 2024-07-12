<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\Buttons\DeleteBackupTaskLogButton;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\User;

test('the component can be rendered', function (): void {

    $backupTask = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $backupTask->id]);
    Livewire::test(DeleteBackupTaskLogButton::class, ['backupTaskLog' => $log])
        ->assertOk();
});

test('the authorized user can delete a task log', function (): void {
    Toaster::fake();

    $user = User::factory()->create();
    $task = BackupTask::factory()->create(['user_id' => $user->id]);
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $component = Livewire::actingAs($user)
        ->test(DeleteBackupTaskLogButton::class, ['backupTaskLog' => $log])
        ->call('delete')
        ->assertRedirect(route('backup-tasks.index'));

    $this->assertModelMissing($log);
    $this->assertAuthenticatedAs($user);

    Toaster::assertDispatched(__('Backup task log has been removed.'));

    $component->assertDispatched('refreshBackupTaskHistory');
});

test('an unauthorized user cannot delete a task log', function (): void {

    $user = User::factory()->create();
    $anotherUser = User::factory()->create();

    $task = BackupTask::factory()->create(['user_id' => $anotherUser->id]);

    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    Livewire::actingAs($user)
        ->test(DeleteBackupTaskLogButton::class, ['backupTaskLog' => $log])
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('backup_task_logs', ['id' => $log->id]);

    $this->assertAuthenticatedAs($user);
});
