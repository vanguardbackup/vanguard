<?php

declare(strict_types=1);

use App\Jobs\RunFileBackupTaskJob;
use App\Models\BackupTask;
use App\Models\RemoteServer;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

// Define the URL directly since route() helper isn't working reliably in tests
function getWebhookUrl(BackupTask $backupTask, ?string $token = null): string
{
    $url = "/webhooks/backup-tasks/{$backupTask->getAttribute('id')}/run";

    if ($token) {
        $url .= "?token={$token}";
    }

    return $url;
}

beforeEach(function (): void {
    RateLimiter::clear('webhook_run_backup_task_1');
});

it('runs backup task with valid token', function (): void {
    Queue::fake();

    $remoteServer = RemoteServer::factory()->create();
    $backupTask = BackupTask::factory()->create([
        'status' => 'ready',
        'type' => 'files',
        'remote_server_id' => $remoteServer->id,
        'webhook_token' => Str::random(64),
    ]);

    $response = $this->postJson(getWebhookUrl($backupTask, $backupTask->webhook_token));

    $response->assertStatus(202)
        ->assertJson(['message' => 'Backup task initiated successfully.']);

    expect($backupTask->fresh()->isRunning())->toBeTrue();

    Queue::assertPushed(RunFileBackupTaskJob::class, function (RunFileBackupTaskJob $runFileBackupTaskJob) use ($backupTask): bool {
        return $runFileBackupTaskJob->backupTaskId === $backupTask->id;
    });
});

it('rejects invalid token', function (): void {
    $backupTask = BackupTask::factory()->create([
        'webhook_token' => 'valid-token-here',
    ]);

    $response = $this->postJson(getWebhookUrl($backupTask, 'invalid-token'));

    $response->assertStatus(403)
        ->assertJson(['message' => 'Invalid token']);

    expect($backupTask->fresh()->isRunning())->toBeFalse();
});

it('rejects when task is paused', function (): void {
    $backupTask = BackupTask::factory()->paused()->create();

    $response = $this->postJson(getWebhookUrl($backupTask, $backupTask->webhook_token));

    $response->assertStatus(409)
        ->assertJson(['message' => 'The backup task is currently paused and cannot be executed.']);

    expect($backupTask->fresh()->isRunning())->toBeFalse();
});

it('rejects when task is already running', function (): void {
    $backupTask = BackupTask::factory()->create([
        'status' => 'running',
    ]);

    $response = $this->postJson(getWebhookUrl($backupTask, $backupTask->webhook_token));

    $response->assertStatus(409)
        ->assertJson(['message' => 'The backup task is already running.']);
});

it('rejects when another task is running on same server', function (): void {
    $remoteServer = RemoteServer::factory()->create();

    // Create an already running task on the server
    BackupTask::factory()->create([
        'status' => 'running',
        'remote_server_id' => $remoteServer->id,
    ]);

    // Create a new task on the same server
    $backupTask = BackupTask::factory()->create([
        'status' => 'ready',
        'remote_server_id' => $remoteServer->id,
    ]);

    $response = $this->postJson(getWebhookUrl($backupTask, $backupTask->webhook_token));

    $response->assertStatus(409)
        ->assertJson(['message' => 'Another task is currently running on the same remote server. Please try again later.']);

    expect($backupTask->fresh()->isRunning())->toBeFalse();
});

it('handles rate limiting', function (): void {
    $backupTask = BackupTask::factory()->create();
    $url = getWebhookUrl($backupTask, $backupTask->webhook_token);

    // Make the maximum allowed requests
    for ($i = 0; $i < 10; $i++) {
        $this->postJson($url);
    }

    // The next request should be rate limited
    $response = $this->postJson($url);

    $response->assertStatus(429)
        ->assertJson(['message' => 'Too many requests. Please try again later.']);
});
