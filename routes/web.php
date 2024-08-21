<?php

use App\Http\Controllers\BackupDestinations;
use App\Http\Controllers\BackupTasks;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\RemoteServers;
use App\Http\Controllers\Tags;
use App\Http\Middleware\UserLanguage;
use App\Livewire\BackupTasks\Forms\CreateBackupTaskForm;
use App\Livewire\BackupTasks\Index;
use App\Livewire\NotificationStreams\Forms\CreateNotificationStream;
use App\Livewire\NotificationStreams\Forms\UpdateNotificationStream;
use App\Livewire\NotificationStreams\Index as NotificationStreamIndex;
use App\Livewire\Profile\APIPage;
use App\Livewire\Profile\ExperimentsPage;
use App\Livewire\Profile\MFAPage;
use App\Livewire\Profile\QuietModePage;
use App\Livewire\Profile\SessionsPage;
use App\Livewire\StatisticsPage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/overview');

Route::middleware([UserLanguage::class, 'auth', 'two-factor'])->group(function () {
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

    Route::prefix('notification-streams')->group(function () {
        Route::get('/', NotificationStreamIndex::class)->name('notification-streams.index');
        Route::get('create', CreateNotificationStream::class)->name('notification-streams.create');
        Route::get('edit/{notificationStream}', UpdateNotificationStream::class)->name('notification-streams.edit')
            ->middleware('can:update,notificationStream');
    });

    Route::get('statistics', StatisticsPage::class)->name('statistics');

    Route::get('profile/api', APIPage::class)->name('profile.api');
    Route::get('profile/mfa', MFAPage::class)->name('profile.mfa');
    Route::get('profile/sessions', SessionsPage::class)->name('profile.sessions');
    Route::get('profile/experiments', ExperimentsPage::class)->name('profile.experiments');
    Route::get('profile/quiet-mode', QuietModePage::class)->name('profile.quiet-mode');
});

require __DIR__ . '/auth.php';
