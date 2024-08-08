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
    Route::get('user', [UserController::class, '__invoke']);

    // Backup Tasks
    Route::get('backup-tasks', [BackupTaskController::class, 'index'])->middleware('ability:view-backup-tasks');
    Route::post('backup-tasks', [BackupTaskController::class, 'store'])->middleware('ability:create-backup-tasks');
    Route::get('backup-tasks/{id}', [BackupTaskController::class, 'show'])->middleware('ability:view-backup-tasks');
    Route::put('backup-tasks/{id}', [BackupTaskController::class, 'update'])->middleware('ability:update-backup-tasks');
    Route::delete('backup-tasks/{id}', [BackupTaskController::class, 'destroy'])->middleware('ability:delete-backup-tasks');

    Route::post('backup-tasks/{id}/run', RunBackupTaskController::class)->middleware('ability:run-backup-tasks');
    Route::get('backup-tasks/{id}/status', BackupTaskStatusController::class)->middleware('ability:view-backup-tasks');
    Route::get('backup-tasks/{id}/latest-log', BackupTaskLatestLogController::class)->middleware('ability:view-backup-tasks');

    // Backup Destinations
    Route::get('backup-destinations', [BackupDestinationController::class, 'index'])->middleware('ability:view-backup-destinations');
    Route::post('backup-destinations', [BackupDestinationController::class, 'store'])->middleware('ability:create-backup-destinations');
    Route::get('backup-destinations/{id}', [BackupDestinationController::class, 'show'])->middleware('ability:view-backup-destinations');
    Route::put('backup-destinations/{id}', [BackupDestinationController::class, 'update'])->middleware('ability:update-backup-destinations');
    Route::delete('backup-destinations/{id}', [BackupDestinationController::class, 'destroy'])->middleware('ability:delete-backup-destinations');

    // Tags
    Route::get('tags', [TagController::class, 'index'])->middleware('ability:manage-tags');
    Route::post('tags', [TagController::class, 'store'])->middleware('ability:manage-tags');
    Route::get('tags/{id}', [TagController::class, 'show'])->middleware('ability:manage-tags');
    Route::put('tags/{id}', [TagController::class, 'update'])->middleware('ability:manage-tags');
    Route::delete('tags/{id}', [TagController::class, 'destroy'])->middleware('ability:manage-tags');

    // Remote Servers
    Route::get('remote-servers', [RemoteServerController::class, 'index'])->middleware('ability:view-remote-servers');
    Route::post('remote-servers', [RemoteServerController::class, 'store'])->middleware('ability:create-remote-servers');
    Route::get('remote-servers/{id}', [RemoteServerController::class, 'show'])->middleware('ability:view-remote-servers');
    Route::put('remote-servers/{id}', [RemoteServerController::class, 'update'])->middleware('ability:update-remote-servers');
    Route::delete('remote-servers/{id}', [RemoteServerController::class, 'destroy'])->middleware('ability:delete-remote-servers');

    // Notification Streams
    Route::get('notification-streams', [NotificationStreamController::class, 'index'])->middleware('ability:view-notification-streams');
    Route::post('notification-streams', [NotificationStreamController::class, 'store'])->middleware('ability:create-notification-streams');
    Route::get('notification-streams/{id}', [NotificationStreamController::class, 'show'])->middleware('ability:view-notification-streams');
    Route::put('notification-streams/{id}', [NotificationStreamController::class, 'update'])->middleware('ability:update-notification-streams');
    Route::delete('notification-streams/{id}', [NotificationStreamController::class, 'destroy'])->middleware('ability:delete-notification-streams');

    // Backup Task Logs
    Route::get('backup-task-logs', [BackupTaskLogController::class, 'index'])->middleware('ability:view-backup-tasks');
    Route::get('backup-task-logs/{id}', [BackupTaskLogController::class, 'show'])->middleware('ability:view-backup-tasks');
    Route::delete('backup-task-logs/{id}', [BackupTaskLogController::class, 'destroy'])->middleware('ability:delete-backup-tasks');
});
