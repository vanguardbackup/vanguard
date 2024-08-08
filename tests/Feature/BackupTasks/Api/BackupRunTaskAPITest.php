<?php

declare(strict_types=1);

use App\Models\BackupTask;
use App\Models\RemoteServer;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Event::fake();
    $this->user = User::factory()->create();
});

test('user with run-backup-tasks permission can run a backup task', function (): void {
    Queue::fake();
    Sanctum::actingAs($this->user, ['run-backup-tasks']);

    $backupTask = BackupTask::factory()->create([
        'user_id' => $this->user->id,
        'status' => BackupTask::STATUS_READY,
    ]);

    $response = $this->postJson("/api/backup-tasks/{$backupTask->id}/run");

    $response->assertStatus(202)
        ->assertJson(['message' => 'Backup task initiated successfully.']);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTask->id,
        'status' => BackupTask::STATUS_RUNNING,
    ]);
});

test('user without run-backup-tasks permission cannot run a backup task', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $backupTask = BackupTask::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->postJson("/api/backup-tasks/{$backupTask->id}/run");

    $response->assertStatus(403)
        ->assertJson(['message' => 'Invalid ability provided.']);
});

test('user cannot run a backup task belonging to another user', function (): void {
    Queue::fake();
    Sanctum::actingAs($this->user, ['run-backup-tasks']);

    $otherUser = User::factory()->create();
    $backupTask = BackupTask::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->postJson("/api/backup-tasks/{$backupTask->id}/run");

    $response->assertStatus(403)
        ->assertJson(['message' => 'Access denied. This backup task does not belong to you.']);
});

test('user cannot run a paused backup task', function (): void {
    Sanctum::actingAs($this->user, ['run-backup-tasks']);

    $backupTask = BackupTask::factory()->paused()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->postJson("/api/backup-tasks/{$backupTask->id}/run");

    $response->assertStatus(409)
        ->assertJson(['message' => 'The backup task is currently paused and cannot be executed.']);
});

test('user cannot run a backup task when another task is running on the same server', function (): void {
    Sanctum::actingAs($this->user, ['run-backup-tasks']);

    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $this->user->id,
    ]);

    BackupTask::factory()->create([
        'user_id' => $this->user->id,
        'remote_server_id' => $remoteServer->id,
        'status' => BackupTask::STATUS_RUNNING,
    ]);

    $backupTask = BackupTask::factory()->create([
        'user_id' => $this->user->id,
        'remote_server_id' => $remoteServer->id,
        'status' => BackupTask::STATUS_READY,
    ]);

    $response = $this->postJson("/api/backup-tasks/{$backupTask->id}/run");

    $response->assertStatus(409)
        ->assertJson(['message' => 'Another task is currently running on the same remote server. Please try again later.']);
});

test('it returns 404 for non-existent backup task', function (): void {
    Sanctum::actingAs($this->user, ['run-backup-tasks']);

    $nonExistentId = BackupTask::max('id') + 1;

    $response = $this->postJson("/api/backup-tasks/{$nonExistentId}/run");

    $response->assertNotFound()
        ->assertJson(['message' => 'Backup task not found']);
});

test('it returns 401 for unauthenticated user', function (): void {
    $backupTask = BackupTask::factory()->create();

    $response = $this->postJson("/api/backup-tasks/{$backupTask->id}/run");

    $response->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Event::fake();
    Queue::fake();
});

test('user is rate limited after exceeding maximum attempts', function (): void {
    Sanctum::actingAs($this->user, ['run-backup-tasks']);

    $backupTask = BackupTask::factory()->create([
        'user_id' => $this->user->id,
        'status' => BackupTask::STATUS_READY,
    ]);

    // Simulate hitting the rate limit
    for ($i = 0; $i < 5; $i++) {
        $this->postJson("/api/backup-tasks/{$backupTask->id}/run");
    }

    // The 6th request should be rate limited
    $response = $this->postJson("/api/backup-tasks/{$backupTask->id}/run");

    $response->assertStatus(429)
        ->assertJson(['message' => 'Too many requests. Please try again later.']);
});

test('rate limit is applied on a per-user basis', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Sanctum::actingAs($user1, ['run-backup-tasks']);

    $backupTask1 = BackupTask::factory()->create([
        'user_id' => $user1->id,
        'status' => BackupTask::STATUS_READY,
    ]);

    // User 1 hits the rate limit
    for ($i = 0; $i < 5; $i++) {
        $this->postJson("/api/backup-tasks/{$backupTask1->id}/run");
    }

    // The 6th request for User 1 should be rate limited
    $response = $this->postJson("/api/backup-tasks/{$backupTask1->id}/run");
    $response->assertStatus(429);

    // Switch to User 2
    Sanctum::actingAs($user2, ['run-backup-tasks']);

    $backupTask2 = BackupTask::factory()->create([
        'user_id' => $user2->id,
        'status' => BackupTask::STATUS_READY,
    ]);

    $response = $this->postJson("/api/backup-tasks/{$backupTask2->id}/run");
    $response->assertStatus(202);
});

test('rate limit resets after decay time', function (): void {
    Sanctum::actingAs($this->user, ['run-backup-tasks']);

    $backupTask = BackupTask::factory()->create([
        'user_id' => $this->user->id,
        'status' => BackupTask::STATUS_READY,
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson("/api/backup-tasks/{$backupTask->id}/run");
    }

    $response = $this->postJson("/api/backup-tasks/{$backupTask->id}/run");
    $response->assertStatus(429);

    $this->travel(70)->seconds();

    $response = $this->postJson("/api/backup-tasks/{$backupTask->id}/run");
    $response->assertStatus(202);
});

afterEach(function (): void {
    RateLimiter::clear('run_backup_task_' . $this->user->id);
});
