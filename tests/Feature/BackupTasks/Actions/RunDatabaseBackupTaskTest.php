<?php

use App\Mail\BackupTaskFailed;
use App\Models\BackupTask;
use App\Models\RemoteServer;
use App\Models\User;
use App\Services\Backup\Tasks\DatabaseBackup;

it('sends a mailable if the database password is missing', function () {
    Mail::fake();
    Event::fake();

    test_create_keys();

    $user = User::factory()->create();

    $remoteServer = RemoteServer::factory()->create([
        'database_password' => null,
        'user_id' => $user->id,
    ]);

    $backupTask = BackupTask::factory()->create([
        'user_id' => $user->id,
        'remote_server_id' => $remoteServer->id,
        'type' => BackupTask::TYPE_DATABASE,
    ]);

    $action = new DatabaseBackup;
    $action->handle($backupTask->id);

    Mail::assertQueued(BackupTaskFailed::class, function ($mail) use ($user, $backupTask) {
        return $mail->hasTo($user->email) &&
            $mail->taskName === $backupTask->label &&
            Str::contains($mail->errorMessage, 'Please provide a database password for the remote server.');
    });

    test_restore_keys();
});
