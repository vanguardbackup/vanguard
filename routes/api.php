<?php

use App\Http\Controllers\Api\BackupDestinationController;
use App\Http\Controllers\Api\BackupTaskController;
use App\Http\Controllers\Api\BackupTaskLatestLogController;
use App\Http\Controllers\Api\BackupTaskLogController;
use App\Http\Controllers\Api\BackupTaskStatusController;
use App\Http\Controllers\Api\NotificationStreamController;
use App\Http\Controllers\Api\RemoteServerController;
use App\Http\Controllers\Api\RunBackupTaskController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('user', UserController::class);
    Route::post('backup-tasks/{id}/run', RunBackupTaskController::class);
    Route::get('backup-tasks/{id}/status', BackupTaskStatusController::class);
    Route::get('backup-tasks/{id}/latest-log', BackupTaskLatestLogController::class);

    $fullResources = [
        'backup-destinations' => BackupDestinationController::class,
        'tags' => TagController::class,
        'remote-servers' => RemoteServerController::class,
        'notification-streams' => NotificationStreamController::class,
        'backup-tasks' => BackupTaskController::class,
    ];

    foreach ($fullResources as $name => $controller) {
        Route::apiResource($name, $controller)->parameters([
            $name => lcfirst(str_replace('-', '', ucwords($name, '-'))),
        ]);
    }

    Route::apiResource('backup-task-logs', BackupTaskLogController::class)
        ->parameters(['backup-task-logs' => 'backupTaskLog'])
        ->only(['index', 'show', 'destroy']);
});
