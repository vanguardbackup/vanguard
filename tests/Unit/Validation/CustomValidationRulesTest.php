<?php

declare(strict_types=1);

use App\Models\BackupTask;
use App\Models\RemoteServer;
use App\Models\User;
use App\Rules\UniqueScheduledTimePerRemoteServer;
use Illuminate\Validation\ValidationException;

it('rejects the same scheduled time for two tasks on the same remote server', function (): void {
    $user = User::factory()->create();

    $remoteServer = RemoteServer::factory()->create();

    BackupTask::factory()->create([
        'remote_server_id' => $remoteServer->id,
        'time_to_run_at' => '12:00',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    $this->assertEquals('12:00', $remoteServer->backupTasks->first()->time_to_run_at);

    Validator::make([
        'time_to_run_at' => '12:00',
        'remote_server_id' => $remoteServer->id,
    ], [
        'time_to_run_at' => ['required', 'string', 'date_format:H:i', new UniqueScheduledTimePerRemoteServer($remoteServer->id)],
    ])->validate();
})->throws(ValidationException::class, 'The scheduled time for this remote server is already taken. Please choose a different time.');
