<?php

declare(strict_types=1);

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->backupTask = BackupTask::factory()->create(['user_id' => $this->user->id]);
});

test('user with view-backup-tasks permission can view latest backup task log', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $log = BackupTaskLog::factory()->create(['backup_task_id' => $this->backupTask->id]);

    $response = $this->getJson("/api/backup-tasks/{$this->backupTask->id}/latest-log");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'backup_task_id',
                'status',
                'output',
                'created_at',
            ],
        ]);
});

test('user without view-backup-tasks permission cannot view latest backup task log', function (): void {
    Sanctum::actingAs($this->user, ['other-permission']);

    $response = $this->getJson("/api/backup-tasks/{$this->backupTask->id}/latest-log");

    $response->assertForbidden()
        ->assertJson(['message' => 'Invalid ability provided.']);
});

test('returns not found for non-existent backup task', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $nonExistentId = $this->backupTask->id + 1;
    $response = $this->getJson("/api/backup-tasks/{$nonExistentId}/latest-log");

    $response->assertNotFound()
        ->assertJson(['message' => 'Backup task not found.']);
});

test('returns not found when backup task has no logs', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $response = $this->getJson("/api/backup-tasks/{$this->backupTask->id}/latest-log");

    $response->assertNotFound()
        ->assertJson(['message' => 'No logs found for this backup task.']);
});

test('user cannot view latest log of backup task belonging to another user', function (): void {
    $otherUser = User::factory()->create();
    $otherBackupTask = BackupTask::factory()->create(['user_id' => $otherUser->id]);

    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $response = $this->getJson("/api/backup-tasks/{$otherBackupTask->id}/latest-log");

    $response->assertForbidden();
});

test('unauthenticated user cannot view latest backup task log', function (): void {
    $response = $this->getJson("/api/backup-tasks/{$this->backupTask->id}/latest-log");

    $response->assertUnauthorized();
});
