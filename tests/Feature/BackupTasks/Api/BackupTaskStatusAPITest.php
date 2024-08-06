<?php

declare(strict_types=1);

use App\Models\BackupTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->backupTask = BackupTask::factory()->create(['user_id' => $this->user->id]);
});

test('user with view-backup-tasks permission can view backup task status', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $response = $this->getJson("/api/backup-tasks/{$this->backupTask->id}/status");

    $response->assertOk()
        ->assertJsonStructure(['status']);
});

test('user without view-backup-tasks permission cannot view backup task status', function (): void {
    Sanctum::actingAs($this->user, ['other-permission']);

    $response = $this->getJson("/api/backup-tasks/{$this->backupTask->id}/status");

    $response->assertForbidden();
});

test('user cannot view status of non-existent backup task', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $nonExistentId = $this->backupTask->id + 1;
    $response = $this->getJson("/api/backup-tasks/{$nonExistentId}/status");

    $response->assertNotFound();
});

test('user cannot view status of backup task belonging to another user', function (): void {
    $otherUser = User::factory()->create();
    $otherBackupTask = BackupTask::factory()->create(['user_id' => $otherUser->id]);

    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $response = $this->getJson("/api/backup-tasks/{$otherBackupTask->id}/status");

    $response->assertForbidden();
});

test('unauthenticated user cannot view backup task status', function (): void {
    $response = $this->getJson("/api/backup-tasks/{$this->backupTask->id}/status");

    $response->assertUnauthorized();
});
