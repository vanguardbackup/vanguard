<?php

declare(strict_types=1);

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->backupTask = BackupTask::factory()->create(['user_id' => $this->user->id]);
    $this->backupTaskLog = BackupTaskLog::factory()->create(['backup_task_id' => $this->backupTask->id]);
});

it('lists backup task logs for authenticated user', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $response = $this->getJson('/api/backup-task-logs');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'backup_task_id', 'output', 'finished_at', 'successful_at', 'created_at'],
            ],
            'links',
            'meta',
        ]);
});

it('shows a specific backup task log', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $response = $this->getJson("/api/backup-task-logs/{$this->backupTaskLog->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $this->backupTaskLog->id,
                'backup_task_id' => $this->backupTask->id,
            ],
        ]);
});

it('prevents unauthorized access to backup task logs', function (): void {
    Sanctum::actingAs($this->user, []);

    $response = $this->getJson('/api/backup-task-logs');

    $response->assertStatus(403);
});

it('returns 404 for non-existent backup task log', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $response = $this->getJson('/api/backup-task-logs/999999');

    $response->assertStatus(404);
});

it('deletes a backup task log', function (): void {
    Sanctum::actingAs($this->user, ['delete-backup-tasks']);

    $response = $this->deleteJson("/api/backup-task-logs/{$this->backupTaskLog->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('backup_task_logs', ['id' => $this->backupTaskLog->id]);
});

it('prevents unauthorized deletion of backup task logs', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $response = $this->deleteJson("/api/backup-task-logs/{$this->backupTaskLog->id}");

    $response->assertStatus(403);
    $this->assertDatabaseHas('backup_task_logs', ['id' => $this->backupTaskLog->id]);
});
