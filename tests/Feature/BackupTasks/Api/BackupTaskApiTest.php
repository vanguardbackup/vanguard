<?php

declare(strict_types=1);

use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\RemoteServer;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('user can list their backup tasks', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    BackupTask::factory()->count(3)->create(['user_id' => $this->user->id]);

    $response = $this->getJson('/api/backup-tasks');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'user_id', 'remote_server_id', 'backup_destination_id', 'label', 'type', 'has_isolated_credentials'],
            ],
            'links',
            'meta',
        ]);
});

test('user cannot list backup tasks without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $response = $this->getJson('/api/backup-tasks');

    $response->assertStatus(403);
});

test('user can create a new backup task', function (): void {
    Sanctum::actingAs($this->user, ['create-backup-tasks']);

    $remoteServer = RemoteServer::factory()->create(['user_id' => $this->user->id]);
    $backupDestination = BackupDestination::factory()->create(['user_id' => $this->user->id]);

    $taskData = BackupTask::factory()->raw([
        'user_id' => $this->user->id,
        'remote_server_id' => $remoteServer->id,
        'backup_destination_id' => $backupDestination->id,
        'time_to_run_at' => '12:00',
        'custom_cron_expression' => null,
    ]);

    $response = $this->postJson('/api/backup-tasks', $taskData);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'label' => $taskData['label'],
            'type' => $taskData['type'],
            'time_to_run_at' => $taskData['time_to_run_at'],
        ]);

    $this->assertDatabaseHas('backup_tasks', [
        'label' => $taskData['label'],
        'user_id' => $this->user->id,
        'time_to_run_at' => $taskData['time_to_run_at'],
    ]);
});

test('user can create a new backup task with custom cron expression', function (): void {
    Sanctum::actingAs($this->user, ['create-backup-tasks']);

    $remoteServer = RemoteServer::factory()->create(['user_id' => $this->user->id]);
    $backupDestination = BackupDestination::factory()->create(['user_id' => $this->user->id]);

    $taskData = BackupTask::factory()->raw([
        'user_id' => $this->user->id,
        'remote_server_id' => $remoteServer->id,
        'backup_destination_id' => $backupDestination->id,
        'time_to_run_at' => null,
        'custom_cron_expression' => '0 0 * * *',
    ]);

    $response = $this->postJson('/api/backup-tasks', $taskData);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'label' => $taskData['label'],
            'type' => $taskData['type'],
            'custom_cron_expression' => $taskData['custom_cron_expression'],
        ]);

    $this->assertDatabaseHas('backup_tasks', [
        'label' => $taskData['label'],
        'user_id' => $this->user->id,
        'custom_cron_expression' => $taskData['custom_cron_expression'],
    ]);
});

test('user cannot create a backup task without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $taskData = BackupTask::factory()->raw(['user_id' => $this->user->id]);

    $response = $this->postJson('/api/backup-tasks', $taskData);

    $response->assertStatus(403);
});

test('user can view a specific backup task', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $task = BackupTask::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/backup-tasks/{$task->id}");

    $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $task->id,
            'label' => $task->label,
            'type' => $task->type,
        ]);
});

test('user cannot view a backup task without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $task = BackupTask::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/backup-tasks/{$task->id}");

    $response->assertStatus(403);
});

test('user can update their backup task', function (): void {
    Sanctum::actingAs($this->user, ['update-backup-tasks']);

    $task = BackupTask::factory()->create([
        'remote_server_id' => RemoteServer::factory()->create(['user_id' => $this->user->id]),
        'backup_destination_id' => BackupDestination::factory()->create(['user_id' => $this->user->id]),
        'user_id' => $this->user->id,
        'time_to_run_at' => '12:00',
    ]);

    $updatedData = [
        'label' => 'Updated Task',
        'description' => 'Updated description',
        'time_to_run_at' => '13:00',
    ];

    $response = $this->putJson("/api/backup-tasks/{$task->id}", $updatedData);

    $response->assertStatus(200)
        ->assertJsonFragment($updatedData);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $task->id,
        'label' => 'Updated Task',
        'description' => 'Updated description',
        'time_to_run_at' => '13:00',
    ]);
});

test('user cannot update a backup task without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $task = BackupTask::factory()->create(['user_id' => $this->user->id]);

    $response = $this->putJson("/api/backup-tasks/{$task->id}", [
        'label' => 'Updated Task',
    ]);

    $response->assertStatus(403);
});

test('user can delete their backup task', function (): void {
    Sanctum::actingAs($this->user, ['delete-backup-tasks']);

    $task = BackupTask::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/backup-tasks/{$task->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('backup_tasks', ['id' => $task->id]);
});

test('user cannot delete a backup task without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $task = BackupTask::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/backup-tasks/{$task->id}");

    $response->assertStatus(403);
});

test('viewing a non-existent backup task returns 404', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $nonExistentId = 9999;
    $response = $this->getJson("/api/backup-tasks/{$nonExistentId}");

    $response->assertStatus(404);
});

test('updating a non-existent backup task returns 404', function (): void {
    Sanctum::actingAs($this->user, ['update-backup-tasks']);

    $nonExistentId = 9999;
    $response = $this->putJson("/api/backup-tasks/{$nonExistentId}", [
        'label' => 'Updated Task',
    ]);

    $response->assertStatus(404);
});

test('deleting a non-existent backup task returns 404', function (): void {
    Sanctum::actingAs($this->user, ['delete-backup-tasks']);

    $nonExistentId = 9999;
    $response = $this->deleteJson("/api/backup-tasks/{$nonExistentId}");

    $response->assertStatus(404);
});

test('user cannot view a backup task belonging to another user', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $anotherUser = User::factory()->create();
    $task = BackupTask::factory()->create(['user_id' => $anotherUser->id]);

    $response = $this->getJson("/api/backup-tasks/{$task->id}");

    $response->assertStatus(403);
});

test('user cannot update a backup task belonging to another user', function (): void {
    Sanctum::actingAs($this->user, ['update-backup-tasks']);

    $anotherUser = User::factory()->create();
    $task = BackupTask::factory()->create(['user_id' => $anotherUser->id]);

    $response = $this->putJson("/api/backup-tasks/{$task->id}", [
        'label' => 'Updated Task',
    ]);

    $response->assertStatus(403);
});

test('user cannot delete a backup task belonging to another user', function (): void {
    Sanctum::actingAs($this->user, ['delete-backup-tasks']);

    $anotherUser = User::factory()->create();
    $task = BackupTask::factory()->create(['user_id' => $anotherUser->id]);

    $response = $this->deleteJson("/api/backup-tasks/{$task->id}");

    $response->assertStatus(403);
});

test('creating a backup task with non-existent remote server returns 422', function (): void {
    Sanctum::actingAs($this->user, ['create-backup-tasks']);

    $backupDestination = BackupDestination::factory()->create(['user_id' => $this->user->id]);

    $taskData = BackupTask::factory()->raw([
        'user_id' => $this->user->id,
        'remote_server_id' => 9999, // Non-existent ID
        'backup_destination_id' => $backupDestination->id,
        'time_to_run_at' => '12:00',
    ]);

    $response = $this->postJson('/api/backup-tasks', $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['remote_server_id'])
        ->assertJsonPath('errors.remote_server_id.0', 'The selected remote server is invalid.');
});

test('creating a backup task with non-existent backup destination returns 422', function (): void {
    Sanctum::actingAs($this->user, ['create-backup-tasks']);

    $remoteServer = RemoteServer::factory()->create(['user_id' => $this->user->id]);

    $taskData = BackupTask::factory()->raw([
        'user_id' => $this->user->id,
        'remote_server_id' => $remoteServer->id,
        'backup_destination_id' => 9999, // Non-existent ID
        'time_to_run_at' => '12:00',
    ]);

    $response = $this->postJson('/api/backup-tasks', $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['backup_destination_id'])
        ->assertJsonPath('errors.backup_destination_id.0', 'The selected backup destination is invalid.');
});

test('creating a backup task with remote server belonging to another user returns 422', function (): void {
    Sanctum::actingAs($this->user, ['create-backup-tasks']);

    $anotherUser = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create(['user_id' => $anotherUser->id]);
    $backupDestination = BackupDestination::factory()->create(['user_id' => $this->user->id]);

    $taskData = BackupTask::factory()->raw([
        'user_id' => $this->user->id,
        'remote_server_id' => $remoteServer->id,
        'backup_destination_id' => $backupDestination->id,
        'time_to_run_at' => '12:00',
    ]);

    $response = $this->postJson('/api/backup-tasks', $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['remote_server_id'])
        ->assertJsonPath('errors.remote_server_id.0', 'The selected remote server is invalid.');
});

test('creating a backup task with backup destination belonging to another user returns 422', function (): void {
    Sanctum::actingAs($this->user, ['create-backup-tasks']);

    $anotherUser = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create(['user_id' => $this->user->id]);
    $backupDestination = BackupDestination::factory()->create(['user_id' => $anotherUser->id]);

    $taskData = BackupTask::factory()->raw([
        'user_id' => $this->user->id,
        'remote_server_id' => $remoteServer->id,
        'backup_destination_id' => $backupDestination->id,
        'time_to_run_at' => '12:00',
    ]);

    $response = $this->postJson('/api/backup-tasks', $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['backup_destination_id'])
        ->assertJsonPath('errors.backup_destination_id.0', 'The selected backup destination is invalid.');
});
