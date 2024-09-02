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
                '*' => [
                    'id',
                    'user_id',
                    'remote_server_id',
                    'backup_destination_id',
                    'label',
                    'source' => [
                        'path',
                        'type',
                        'database_name',
                        'excluded_tables',
                    ],
                    'schedule' => [
                        'frequency',
                        'scheduled_utc_time',
                        'scheduled_local_time',
                        'custom_cron',
                    ],
                    'storage' => [
                        'max_backups',
                        'appended_filename',
                        'path',
                    ],
                    'notification_streams_count',
                    'status',
                    'has_encryption_password',
                    'last_run_local_time',
                    'last_run_utc_time',
                    'created_at',
                    'updated_at',
                ],
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
        'store_path' => '/blah/blah',
        'status' => 'ready',
    ]);

    $response = $this->postJson('/api/backup-tasks', $taskData);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'label' => $taskData['label'],
            'description' => $taskData['description'],
            'source' => [
                'path' => $taskData['source_path'],
                'type' => $taskData['type'],
                'database_name' => $taskData['database_name'] ?? null,
                'excluded_tables' => $taskData['excluded_database_tables'] ?? null,
            ],
            'schedule' => [
                'frequency' => $taskData['frequency'],
                'scheduled_utc_time' => $taskData['time_to_run_at'],
                'scheduled_local_time' => $taskData['time_to_run_at'],
                'custom_cron' => $taskData['custom_cron_expression'],
            ],
            'storage' => [
                'max_backups' => $taskData['maximum_backups_to_keep'],
                'appended_filename' => $taskData['appended_filename'] ?? null,
                'path' => $taskData['store_path'],
            ],
            'status' => $taskData['status'],
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
        'store_path' => '/blah/blah',
        'status' => 'ready',
    ]);

    $response = $this->postJson('/api/backup-tasks', $taskData);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'label' => $taskData['label'],
            'source' => [
                'path' => $taskData['source_path'],
                'type' => $taskData['type'],
                'database_name' => $taskData['database_name'] ?? null,
                'excluded_tables' => $taskData['excluded_database_tables'] ?? null,
            ],
            'schedule' => [
                'frequency' => $taskData['frequency'],
                'scheduled_utc_time' => $taskData['time_to_run_at'],
                'scheduled_local_time' => $taskData['time_to_run_at'],
                'custom_cron' => $taskData['custom_cron_expression'],
            ],
            'storage' => [
                'max_backups' => $taskData['maximum_backups_to_keep'],
                'appended_filename' => $taskData['appended_filename'] ?? null,
                'path' => $taskData['store_path'],
            ],
            'status' => $taskData['status'] ?? 'ready',
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

    $task = BackupTask::factory()
        ->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/backup-tasks/{$task->id}");

    $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $task->id,
            'label' => $task->label,
            'source' => [
                'path' => $task->source_path,
                'type' => $task->type,
                'database_name' => $task->database_name,
                'excluded_tables' => $task->excluded_database_tables,
            ],
            'schedule' => [
                'frequency' => $task->frequency,
                'scheduled_utc_time' => $task->time_to_run_at,
                'scheduled_local_time' => $task->runTimeFormatted(),
                'custom_cron' => $task->custom_cron_expression,
            ],
            'storage' => [
                'max_backups' => $task->maximum_backups_to_keep,
                'appended_filename' => $task->appended_filename,
                'path' => $task->store_path,
            ],
            'status' => $task->status,
            'has_encryption_password' => ! is_null($task->encryption_password),
            'last_run_local_time' => $task->lastRunFormatted(),
            'last_run_utc_time' => $task->last_run_at,
            'paused_at' => $task->paused_at,
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
        ->assertJsonFragment([
            'label' => 'Updated Task',
            'description' => 'Updated description',
            'schedule' => [
                'frequency' => $task->frequency,
                'scheduled_local_time' => '13:00',
                'scheduled_utc_time' => '13:00',
                'custom_cron' => $task->custom_cron_expression,
            ],
        ]);

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
