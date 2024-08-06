<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'timezone' => 'UTC',
        'language' => 'en',
    ]);
});

test('unauthenticated user cannot access user information', function (): void {
    $response = $this->getJson('/api/user');

    $response->assertUnauthorized();
});

test('authenticated user can access their own information', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/user');

    $response->assertOk();
});

test('user resource contains all expected sections', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/user');

    $response->assertJsonStructure([
        'data' => [
            'id',
            'personal_info' => [
                'name',
                'first_name',
                'last_name',
                'email',
                'avatar_url',
            ],
            'account_settings' => [
                'timezone',
                'language',
                'is_admin',
                'github_login_enabled',
                'weekly_summary_enabled',
            ],
            'backup_tasks' => [
                'total',
                'active',
                'logs' => [
                    'total',
                    'today',
                ],
            ],
            'related_entities' => [
                'remote_servers',
                'backup_destinations',
                'tags',
                'notification_streams',
            ],
            'timestamps' => [
                'account_created',
            ],
        ],
    ]);
});

test('user resource contains correct personal information', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/user');

    $response->assertJsonPath('data.personal_info.name', 'John Doe')
        ->assertJsonPath('data.personal_info.email', 'john@example.com');
});

test('user resource contains correct account settings', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/user');

    $response->assertJsonPath('data.account_settings.timezone', 'UTC')
        ->assertJsonPath('data.account_settings.language', 'en');
});

test('user resource contains correct timestamps', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/user');

    $accountCreated = $response->json('data.timestamps.account_created');
    expect($accountCreated)->not->toBeNull()
        ->and(strtotime((string) $accountCreated))->toBeGreaterThan(0);
});

test('backup task counts are present and numeric', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/user');

    $backupTasks = $response->json('data.backup_tasks');
    expect($backupTasks['total'])->toBeNumeric()
        ->and($backupTasks['active'])->toBeNumeric()
        ->and($backupTasks['logs']['total'])->toBeNumeric()
        ->and($backupTasks['logs']['today'])->toBeNumeric();
});

test('related entity counts are present and numeric', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/user');

    $relatedEntities = $response->json('data.related_entities');
    expect($relatedEntities['remote_servers'])->toBeNumeric()
        ->and($relatedEntities['backup_destinations'])->toBeNumeric()
        ->and($relatedEntities['tags'])->toBeNumeric()
        ->and($relatedEntities['notification_streams'])->toBeNumeric();
});
