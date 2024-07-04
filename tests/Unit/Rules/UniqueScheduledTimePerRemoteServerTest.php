<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\BackupTask;
use App\Models\RemoteServer;
use App\Models\User;
use App\Rules\UniqueScheduledTimePerRemoteServer;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create(['timezone' => 'UTC']);
    Auth::shouldReceive('user')->andReturn($this->user);
    $this->remoteServer = RemoteServer::factory()->create(['user_id' => $this->user->id]);
    $this->remoteServerId = $this->remoteServer->id;
});

describe('UniqueScheduledTimePerRemoteServer', function (): void {
    it('passes when no conflicting tasks', function (): void {
        $rule = new UniqueScheduledTimePerRemoteServer($this->remoteServerId);
        expect(validateRule($rule, '12:00'))->toBeTrue();
    });

    it('fails when conflicting task exists', function (): void {
        BackupTask::factory()->create([
            'user_id' => $this->user->id,
            'remote_server_id' => $this->remoteServerId,
            'time_to_run_at' => '12:00',
        ]);

        $rule = new UniqueScheduledTimePerRemoteServer($this->remoteServerId);
        expect(validateRule($rule, '12:00'))->toBeFalse();
    });

    it('passes when conflicting task exists but different server', function (): void {
        $anotherServer = RemoteServer::factory()->create(['user_id' => $this->user->id]);
        BackupTask::factory()->create([
            'user_id' => $this->user->id,
            'remote_server_id' => $anotherServer->id,
            'time_to_run_at' => '12:00',
        ]);

        $rule = new UniqueScheduledTimePerRemoteServer($this->remoteServerId);
        expect(validateRule($rule, '12:00'))->toBeTrue();
    });

    it('passes when updating existing task', function (): void {
        $task = BackupTask::factory()->create([
            'user_id' => $this->user->id,
            'remote_server_id' => $this->remoteServerId,
            'time_to_run_at' => '12:00',
        ]);

        $rule = new UniqueScheduledTimePerRemoteServer($this->remoteServerId, $task->id);
        expect(validateRule($rule, '12:00'))->toBeTrue();
    });

    it('handles different timezones', function (): void {
        $this->user->timezone = 'America/New_York';
        $this->user->save();

        $existingTask = BackupTask::factory()->create([
            'user_id' => $this->user->id,
            'remote_server_id' => $this->remoteServerId,
            'time_to_run_at' => '12:00', // UTC
        ]);

        Log::info('Test: Created existing task', ['task_id' => $existingTask->id, 'time' => $existingTask->time_to_run_at]);

        $rule = new UniqueScheduledTimePerRemoteServer($this->remoteServerId);

        // 08:00 New York time is 12:00 UTC
        $utcTime = '12:00';

        Log::info('Test: Time for validation', ['utc_time' => $utcTime]);

        $result = validateRule($rule, $utcTime);
        Log::info('Test: Validation result for conflicting time', ['result' => $result]);
        expect($result)->toBeFalse();

        $result = validateRule($rule, '13:00');
        Log::info('Test: Validation result for non-conflicting time', ['result' => $result]);
        expect($result)->toBeTrue();
    });

    it('handles invalid time format', function (): void {
        $rule = new UniqueScheduledTimePerRemoteServer($this->remoteServerId);
        expect(validateRule($rule, 'invalid_time'))->toBeTrue();
    });
});

function validateRule(UniqueScheduledTimePerRemoteServer $rule, string $value): bool
{
    $fails = false;
    $fail = function () use (&$fails): void {
        $fails = true;
    };

    $rule->validate('time_to_run_at', $value, $fail);

    return ! $fails;
}
