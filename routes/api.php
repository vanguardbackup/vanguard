<?php

use App\Http\Controllers\Api\AuthenticateDeviceController;
use App\Http\Controllers\Api\BackupDestinationController;
use App\Http\Controllers\Api\BackupTaskController;
use App\Http\Controllers\Api\BackupTaskLatestLogController;
use App\Http\Controllers\Api\BackupTaskLogController;
use App\Http\Controllers\Api\BackupTaskStatusController;
use App\Http\Controllers\Api\NotificationStreamController;
use App\Http\Controllers\Api\RemoteServerController;
use App\Http\Controllers\Api\RunBackupTaskController;
use App\Http\Controllers\Api\ScheduledBackupTasksController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ViewPublicSSHKeyController;
use App\Http\Middleware\TrackAPIUsage;
use Illuminate\Support\Facades\Route;

/**
 * API Routes
 *
 * This file defines the routes for the Vanguard API.
 * All routes are protected by Sanctum authentication and rate limiting.
 */

/**
 * Device Authentication
 *
 * This route is used to authenticate devices and generate Sanctum tokens.
 * It is not protected by authentication middleware as it's used to obtain the initial token.
 */
Route::post('sanctum/token', AuthenticateDeviceController::class);

Route::middleware(['auth:sanctum', 'throttle:60,1,default', TrackAPIUsage::class])->group(function () {
    /**
     * Get authenticated user information
     */
    Route::get('user', [UserController::class, '__invoke']);

    /**
     * Read Operations
     *
     * These routes are for retrieving data and have a higher rate limit.
     */
    Route::middleware('throttle:100,1,default')->group(function () {
        /**
         * Backup Tasks
         */
        Route::get('backup-tasks/upcoming', [ScheduledBackupTasksController::class, '__invoke'])->middleware('ability:view-backup-tasks');
        Route::get('backup-tasks', [BackupTaskController::class, 'index'])->middleware('ability:view-backup-tasks');
        Route::get('backup-tasks/{id}', [BackupTaskController::class, 'show'])->middleware('ability:view-backup-tasks');
        Route::get('backup-tasks/{id}/status', BackupTaskStatusController::class)
            ->middleware(['ability:view-backup-tasks', 'throttle:20,1,backup-status']);
        Route::get('backup-tasks/{id}/latest-log', BackupTaskLatestLogController::class)->middleware('ability:view-backup-tasks');

        /**
         * Backup Destinations
         */
        Route::get('backup-destinations', [BackupDestinationController::class, 'index'])->middleware('ability:view-backup-destinations');
        Route::get('backup-destinations/{id}', [BackupDestinationController::class, 'show'])->middleware('ability:view-backup-destinations');

        /**
         * Tags
         */
        Route::get('tags', [TagController::class, 'index'])->middleware('ability:manage-tags');
        Route::get('tags/{id}', [TagController::class, 'show'])->middleware('ability:manage-tags');

        /**
         * Remote Servers
         */
        Route::get('remote-servers', [RemoteServerController::class, 'index'])->middleware('ability:view-remote-servers');
        Route::get('remote-servers/{id}', [RemoteServerController::class, 'show'])->middleware('ability:view-remote-servers');

        /**
         * Notification Streams
         */
        Route::get('notification-streams', [NotificationStreamController::class, 'index'])->middleware('ability:view-notification-streams');
        Route::get('notification-streams/{id}', [NotificationStreamController::class, 'show'])->middleware('ability:view-notification-streams');

        /**
         * Backup Task Logs
         */
        Route::get('backup-task-logs', [BackupTaskLogController::class, 'index'])->middleware('ability:view-backup-tasks');
        Route::get('backup-task-logs/{id}', [BackupTaskLogController::class, 'show'])->middleware('ability:view-backup-tasks');
    });

    /**
     * Write Operations
     *
     * These routes are for creating, updating, or deleting data and have a lower rate limit.
     */
    Route::middleware('throttle:30,1,default')->group(function () {
        /**
         * Backup Tasks
         */
        Route::post('backup-tasks', [BackupTaskController::class, 'store'])->middleware('ability:create-backup-tasks');
        Route::put('backup-tasks/{id}', [BackupTaskController::class, 'update'])->middleware('ability:update-backup-tasks');
        Route::delete('backup-tasks/{id}', [BackupTaskController::class, 'destroy'])->middleware('ability:delete-backup-tasks');

        /**
         * Backup Destinations
         */
        Route::post('backup-destinations', [BackupDestinationController::class, 'store'])->middleware('ability:create-backup-destinations');
        Route::put('backup-destinations/{id}', [BackupDestinationController::class, 'update'])->middleware('ability:update-backup-destinations');
        Route::delete('backup-destinations/{id}', [BackupDestinationController::class, 'destroy'])->middleware('ability:delete-backup-destinations');

        /**
         * Tags
         */
        Route::post('tags', [TagController::class, 'store'])->middleware('ability:manage-tags');
        Route::put('tags/{id}', [TagController::class, 'update'])->middleware('ability:manage-tags');
        Route::delete('tags/{id}', [TagController::class, 'destroy'])->middleware('ability:manage-tags');

        /**
         * Remote Servers
         */
        Route::post('remote-servers', [RemoteServerController::class, 'store'])->middleware('ability:create-remote-servers');
        Route::put('remote-servers/{id}', [RemoteServerController::class, 'update'])->middleware('ability:update-remote-servers');
        Route::delete('remote-servers/{id}', [RemoteServerController::class, 'destroy'])->middleware('ability:delete-remote-servers');

        /**
         * View Public SSH Key
         */
        Route::get('ssh-key', [ViewPublicSSHKeyController::class, '__invoke'])->middleware('ability:create-remote-servers');

        /**
         * Notification Streams
         */
        Route::post('notification-streams', [NotificationStreamController::class, 'store'])->middleware('ability:create-notification-streams');
        Route::put('notification-streams/{id}', [NotificationStreamController::class, 'update'])->middleware('ability:update-notification-streams');
        Route::delete('notification-streams/{id}', [NotificationStreamController::class, 'destroy'])->middleware('ability:delete-notification-streams');

        /**
         * Backup Task Logs
         */
        Route::delete('backup-task-logs/{id}', [BackupTaskLogController::class, 'destroy'])->middleware('ability:delete-backup-tasks');
    });

    /**
     * Backup Task Execution
     *
     * This route is for running backup tasks.
     * Rate limiting is implemented within the controller itself.
     */
    Route::post('backup-tasks/{id}/run', RunBackupTaskController::class)
        ->middleware(['ability:run-backup-tasks']);
});
