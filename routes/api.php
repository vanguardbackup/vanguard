<?php

use App\Http\Controllers\Api\BackupDestinationController;
use App\Http\Controllers\Api\BackupTaskController;
use App\Http\Controllers\Api\NotificationStreamController;
use App\Http\Controllers\Api\RemoteServerController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('backup-destinations', BackupDestinationController::class)
        ->parameters([
            'backup-destinations' => 'backupDestination',
        ]);

    Route::apiResource('tags', TagController::class)
        ->parameters([
            'tags' => 'tag',
        ]);

    Route::apiResource('remote-servers', RemoteServerController::class)
        ->parameters([
            'remote-servers' => 'remoteServer',
        ]);

    Route::apiResource('notification-streams', NotificationStreamController::class)
        ->parameters([
            'notification-streams' => 'notificationStream',
        ]);

    Route::apiResource('backup-tasks', BackupTaskController::class)
        ->parameters([
            'backup-tasks' => 'backupTask',
        ]);
});
