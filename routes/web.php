<?php

use App\Http\Controllers\BackupDestinations;
use App\Http\Controllers\BackupTasks;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\RemoteServers;
use App\Http\Controllers\Tags;
use App\Http\Middleware\UserLanguage;
use App\Livewire\BackupTasks\Forms\CreateBackupTaskForm;
use App\Livewire\BackupTasks\Index;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/overview');

Route::middleware([UserLanguage::class, 'auth'])->group(function () {
    Route::get('overview', OverviewController::class)->name('overview');

    Route::view('profile', 'profile')->name('profile');
    Route::view('profile/remove', 'account.remove-account')->name('account.remove-account');

    Route::prefix('remote-servers')->group(function () {
        Route::view('/', 'remote-servers.index')->name('remote-servers.index');
        Route::view('create', 'remote-servers.create')->name('remote-servers.create');
        Route::get('edit/{remoteServer}', RemoteServers\EditController::class)
            ->name('remote-servers.edit')
            ->middleware('can:update,remoteServer');
    });

    Route::prefix('backup-destinations')->group(function () {
        Route::view('/', 'backup-destinations.index')->name('backup-destinations.index');
        Route::view('create', 'backup-destinations.create')->name('backup-destinations.create');
        Route::get('edit/{backupDestination}', BackupDestinations\EditController::class)
            ->name('backup-destinations.edit')
            ->middleware('can:update,backupDestination');
    });

    Route::prefix('backup-tasks')->group(function () {
        Route::get('/', Index::class)->name('backup-tasks.index');
        Route::get('create', CreateBackupTaskForm::class)->name('backup-tasks.create');
        Route::get('edit/{backupTask}', BackupTasks\EditController::class)
            ->name('backup-tasks.edit')
            ->middleware('can:update,backupTask');
    });

    Route::prefix('tags')->group(function () {
        Route::view('/', 'tags.index')->name('tags.index');
        Route::view('create', 'tags.create')->name('tags.create');
        Route::get('edit/{tag}', Tags\EditController::class)
            ->name('tags.edit')
            ->middleware('can:update,tag');
    });
});

require __DIR__ . '/auth.php';
