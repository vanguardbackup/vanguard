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

test('user with view-backup-tasks permission can view upcoming tasks with correct json structure', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-tasks']);

    $response = $this->getJson('/api/backup-tasks/upcoming');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'backup_task_id',
                    'label',
                    'type',
                    'next_run',
                    'next_run_human',
                ],
            ],
        ]);

    // Test the first item structure and data types
    if (count($response->json('data')) > 0) {
        $firstItem = $response->json('data.0');

        $this->assertIsString($firstItem['label']);
        $this->assertIsString($firstItem['type']);
        // next_run can be null or string (ISO date)
        if ($firstItem['next_run'] !== null) {
            $this->assertIsString($firstItem['next_run']);
        }
        // next_run_human can be null or string
        if ($firstItem['next_run_human'] !== null) {
            $this->assertIsString($firstItem['next_run_human']);
        }
    }
});

test('user without view-backup-tasks permission cannot view latest backup task log', function (): void {
    Sanctum::actingAs($this->user, ['other-permission']);

    $response = $this->getJson('/api/backup-tasks/upcoming');

    $response->assertForbidden()
        ->assertJson(['message' => 'Access denied due to insufficient permissions. Required token ability scopes: view-backup-tasks']);
});

test('it returns empty if there are no backup tasks setup for the user', function (): void {
    $user2 = User::factory()->create();
    Sanctum::actingAs($user2, ['view-backup-tasks']);

    $response = $this->getJson('/api/backup-tasks/upcoming');

    $response->assertJsonCount(0, 'data');
});

test('unauthenticated user cannot view upcoming tasks', function (): void {
    $response = $this->getJson('/api/backup-tasks/upcoming');

    $response->assertUnauthorized();
});
