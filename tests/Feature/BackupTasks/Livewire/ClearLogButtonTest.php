<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\Buttons\ClearLogButton;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\User;

test('the component can be rendered', function (): void {

    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(ClearLogButton::class);
    $component->assertOk();
});

test('the log can be cleared', function (): void {

    $user = User::factory()->create();

    $this->actingAs($user);

    $backupTask = BackupTask::factory()->create([
        'user_id' => $user->id,
    ]);

    BackupTaskLog::factory()->create([
        'backup_task_id' => $backupTask->id,
        'finished_at' => now(),
    ]);

    $this->assertDatabaseCount('backup_task_logs', 1);

    $this->assertEquals(1, $user->backupTaskLogCount());

    $component = Livewire::test(ClearLogButton::class);
    $component->call('clearAllLogs');

    $this->assertDatabaseCount('backup_task_logs', 0);
});
