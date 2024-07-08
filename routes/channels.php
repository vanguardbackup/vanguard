<?php

use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\RemoteServer;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('backup-destinations.{id}', function ($user, $id) {
    $backupDestination = BackupDestination::find($id);

    return $user->id === $backupDestination->user_id;
});

Broadcast::channel('remote-servers.{id}', function ($user, $id) {
    $remoteServer = RemoteServer::find($id);

    return $user->id === $remoteServer->user_id;
});

Broadcast::channel('backup-tasks.{id}', function ($user, $id) {
    $backupTask = BackupTask::find($id);

    return $user->id === $backupTask->user_id;
});

Broadcast::channel('new-backup-task-log.{id}', function ($user, $id) {
    $backupTask = BackupTask::find($id);

    return $user->id === $backupTask->user_id;
});

Broadcast::channel('backup-task-log.{id}', function ($user, $id) {
    $backupTask = BackupTask::find($id);

    return $user->id === $backupTask->user_id;
});
