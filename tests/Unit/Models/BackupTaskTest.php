<?php

declare(strict_types=1);

use App\Jobs\BackupTasks\SendDiscordNotificationJob;
use App\Jobs\BackupTasks\SendPushoverNotificationJob;
use App\Jobs\BackupTasks\SendSlackNotificationJob;
use App\Jobs\BackupTasks\SendTeamsNotificationJob;
use App\Jobs\BackupTasks\SendTelegramNotificationJob;
use App\Jobs\RunDatabaseBackupTaskJob;
use App\Jobs\RunFileBackupTaskJob;
use App\Mail\BackupTasks\OutputMail;
use App\Models\BackupTask;
use App\Models\BackupTaskData;
use App\Models\BackupTaskLog;
use App\Models\NotificationStream;
use App\Models\RemoteServer;
use App\Models\Script;
use App\Models\Tag;
use App\Models\Traits\ComposesTelegramNotification;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;

uses(ComposesTelegramNotification::class);

it('sets the last run at timestamp', function (): void {

    $task = BackupTask::factory()->create();

    $this->assertNull($task->last_run_at);

    $task->updateLastRanAt();

    $this->assertNotNull($task->last_run_at);
    $this->assertGreaterThan(now()->subMinute(), $task->last_run_at);
});

it('returns true if using custom cron expression', function (): void {

    $task = BackupTask::factory()->create(['custom_cron_expression' => '* * * * *']);

    $this->assertTrue($task->usingCustomCronExpression());
});

it('returns false if not using custom cron expression', function (): void {

    $task = BackupTask::factory()->create(['custom_cron_expression' => null]);

    $this->assertFalse($task->usingCustomCronExpression());
});

it('returns true if the backup task is ready', function (): void {

    $task = BackupTask::factory()->create(['status' => 'ready']);

    $this->assertTrue($task->isReady());
});

it('returns false if the backup task is not ready', function (): void {

    $task = BackupTask::factory()->create(['status' => 'running']);

    $this->assertFalse($task->isReady());
});

it('returns true if the backup task is running', function (): void {

    $task = BackupTask::factory()->create(['status' => 'running']);

    $this->assertTrue($task->isRunning());
});

it('returns false if the backup task is not running', function (): void {

    $task = BackupTask::factory()->create(['status' => 'ready']);

    $this->assertFalse($task->isRunning());
});

it('sets the backup task to running', function (): void {

    $task = BackupTask::factory()->create(['status' => 'ready']);

    $task->markAsRunning();

    $this->assertTrue($task->isRunning());
});

it('sets the backup task to ready', function (): void {

    $task = BackupTask::factory()->create(['status' => 'running']);

    $task->markAsReady();

    $this->assertTrue($task->isReady());
});

it('returns true if the backup task is eligible to run now', function (): void {

    $task = BackupTask::factory()->create(['status' => 'ready', 'custom_cron_expression' => '* * * * *']);

    $this->assertTrue($task->eligibleToRunNow());
});

it('returns false if the backup task is not eligible to run now', function (): void {

    $task = BackupTask::factory()->create(['status' => 'running', 'custom_cron_expression' => '* * * * *']);

    $this->assertFalse($task->eligibleToRunNow());
});

it('returns true if the custom cron expression matches', function (): void {

    $task = BackupTask::factory()->create(['custom_cron_expression' => '* * * * *']);

    $this->assertTrue($task->cronExpressionMatches());
});

it('returns false if the custom cron expression does not match', function (): void {

    $task = BackupTask::factory()->create(['custom_cron_expression' => '0 0 1 1 *']);

    $this->assertFalse($task->cronExpressionMatches());
});

it('returns true if the backup task is ready to run now', function (): void {

    $task = BackupTask::factory()->create(['status' => 'ready', 'time_to_run_at' => now()->format('H:i')]);

    $this->assertTrue($task->eligibleToRunNow());
});

it('returns false if the backup task is not ready to run now', function (): void {

    $task = BackupTask::factory()->create(['status' => 'running', 'time_to_run_at' => now()->format('H:i')]);

    $this->assertFalse($task->eligibleToRunNow());
});

it('returns true if daily frequency is set', function (): void {

    $task = BackupTask::factory()->create(['frequency' => 'daily']);

    $this->assertTrue($task->isDaily());
});

it('returns false if daily frequency is not set', function (): void {

    $task = BackupTask::factory()->create(['frequency' => 'weekly']);

    $this->assertFalse($task->isDaily());
});

it('returns true if weekly frequency is set', function (): void {

    $task = BackupTask::factory()->create(['frequency' => 'weekly']);

    $this->assertTrue($task->isWeekly());
});

it('returns false if weekly frequency is not set', function (): void {

    $task = BackupTask::factory()->create(['frequency' => 'daily']);

    $this->assertFalse($task->isWeekly());
});

it('returns true if it is the right time to run a daily task', function (): void {

    $task = BackupTask::factory()->create(['time_to_run_at' => now()->format('H:i')]);

    $this->assertTrue($task->isTheRightTimeToRun());
    $this->assertTrue($task->isDaily());
});

it('returns false if it is not the right time to run a daily task', function (): void {

    $task = BackupTask::factory()->create(['time_to_run_at' => now()->subHour()->format('H:i')]);

    $this->assertFalse($task->isTheRightTimeToRun());
    $this->assertTrue($task->isDaily());
});

it('returns true if it is the right time to run a weekly task and last scheduled weekly run is null', function (): void {

    $task = BackupTask::factory()->create([
        'time_to_run_at' => now()->format('H:i'),
        'last_scheduled_weekly_run_at' => null,
        'frequency' => 'weekly',
    ]);

    $this->assertTrue($task->isTheRightTimeToRun());
    $this->assertTrue($task->isWeekly());
});

it('returns true if it is the right time to run a weekly task and last scheduled weekly run is a week ago', function (): void {

    $task = BackupTask::factory()->create([
        'time_to_run_at' => now()->format('H:i'),
        'last_scheduled_weekly_run_at' => now()->subWeek(),
        'frequency' => 'weekly',
    ]);

    $this->assertTrue($task->isTheRightTimeToRun());
    $this->assertTrue($task->isWeekly());
});

it('returns false if it is not the right time to run a weekly task', function (): void {

    $task = BackupTask::factory()->create([
        'time_to_run_at' => now()->subHour()->format('H:i'),
        'last_scheduled_weekly_run_at' => now()->subWeek(),
        'frequency' => 'weekly',
    ]);

    $this->assertFalse($task->isTheRightTimeToRun());
    $this->assertTrue($task->isWeekly());
});

it('returns false if the time to run is in the past', function (): void {

    $task = BackupTask::factory()->create(['time_to_run_at' => now()->subHour()->format('H:i')]);

    $this->assertFalse($task->isTheRightTimeToRun());
});

it('returns false if the time to run is in the past in eligibleToRunNow', function (): void {

    $task = BackupTask::factory()->create(['status' => 'ready', 'time_to_run_at' => now()->subHour()->format('H:i')]);

    $this->assertFalse($task->eligibleToRunNow());
});

it('returns false for eligibleToRun if the task is paused', function (): void {

    $task = BackupTask::factory()->paused()->create();

    $this->assertFalse($task->eligibleToRunNow());
});

it('updates the last scheduled weekly run for a weekly task', function (): void {

    $task = BackupTask::factory()->create([
        'time_to_run_at' => now()->format('H:i'),
        'last_scheduled_weekly_run_at' => null,
        'frequency' => 'weekly',
    ]);

    $task->updateScheduledWeeklyRun();

    $this->assertNotNull($task->last_scheduled_weekly_run_at);
    $this->assertTrue($task->last_scheduled_weekly_run_at->isToday());
});

it('does not update the last scheduled weekly run for a daily task', function (): void {

    $task = BackupTask::factory()->create([
        'time_to_run_at' => now()->format('H:i'),
        'last_scheduled_weekly_run_at' => null,
        'frequency' => 'daily',
    ]);

    $task->updateScheduledWeeklyRun();

    $this->assertNull($task->last_scheduled_weekly_run_at);
});

it('returns false if the custom cron expression does not match in eligibleToRunNow', function (): void {

    $task = BackupTask::factory()->create(['status' => 'ready', 'custom_cron_expression' => '0 0 1 1 *']);

    $this->assertFalse($task->eligibleToRunNow());
});

it('returns false if the backup task is not ready in eligibleToRunNow', function (): void {

    $task = BackupTask::factory()->create(['status' => 'not_ready', 'time_to_run_at' => now()->format('H:i')]);

    $this->assertFalse($task->eligibleToRunNow());
});

it('returns false if the backup task is running in eligibleToRunNow', function (): void {

    $task = BackupTask::factory()->create(['status' => 'running', 'time_to_run_at' => now()->format('H:i')]);

    $this->assertFalse($task->eligibleToRunNow());
});

it('does not update the last scheduled weekly run for a non-weekly task', function (): void {

    $task = BackupTask::factory()->create(['frequency' => 'daily']);

    $task->updateScheduledWeeklyRun();
    $this->assertNull($task->last_scheduled_weekly_run_at);
});

it('does not run if the run time is in the past', function (): void {

    $task = BackupTask::factory()->create(['time_to_run_at' => now()->subHour()->format('H:i')]);

    $this->assertFalse($task->eligibleToRunNow());
});

it('returns true if the user is rotating backups', function (): void {

    $task = BackupTask::factory()->create(['maximum_backups_to_keep' => 5]);

    $this->assertTrue($task->isRotatingBackups());
});

it('returns false if the user is not rotating backups', function (): void {

    $task = BackupTask::factory()->create(['maximum_backups_to_keep' => 0]);

    $this->assertFalse($task->isRotatingBackups());
});

it('scopes all ready tasks', function (): void {

    BackupTask::factory()->create(['status' => 'ready']);
    BackupTask::factory()->create(['status' => 'running']);

    $tasks = BackupTask::ready()->get();

    $this->assertCount(1, $tasks);
    $this->assertEquals('ready', $tasks->first()->status);
});

it('runs the files job', function (): void {
    Queue::fake();

    $remoteServer = RemoteServer::factory()->create();
    $task = BackupTask::factory()->create(['status' => 'ready', 'type' => 'files', 'remote_server_id' => $remoteServer->id]);

    $task->run();

    Queue::assertPushed(RunFileBackupTaskJob::class);
});

it('runs the database job', function (): void {
    Queue::fake();

    $remoteServer = RemoteServer::factory()->create();
    $task = BackupTask::factory()->create(['status' => 'ready', 'type' => 'database', 'remote_server_id' => $remoteServer->id]);

    $task->run();

    Queue::assertPushed(RunDatabaseBackupTaskJob::class);
});

it('does not run the files job if the task has been paused', function (): void {
    Queue::fake();
    $remoteServer = RemoteServer::factory()->create();
    $task = BackupTask::factory()->paused()->create(['status' => 'ready', 'type' => 'files', 'remote_server_id' => $remoteServer->id]);

    $task->run();

    Queue::assertNotPushed(RunFileBackupTaskJob::class);
});

it('does not run the database job if the task has been paused', function (): void {
    Queue::fake();
    $remoteServer = RemoteServer::factory()->create();
    $task = BackupTask::factory()->paused()->create(['status' => 'ready', 'type' => 'database', 'remote_server_id' => $remoteServer->id]);

    $task->run();

    Queue::assertNotPushed(RunDatabaseBackupTaskJob::class);
});

it('does not run the files job if a task is already running on the same remote server', function (): void {
    Queue::fake();

    $remoteServer = RemoteServer::factory()->create();

    $runningTask = BackupTask::factory()->create(['status' => 'running', 'type' => 'files', 'remote_server_id' => $remoteServer->id]);

    $task = BackupTask::factory()->create(['status' => 'ready', 'type' => 'files', 'remote_server_id' => $remoteServer->id]);

    $task->run();

    Queue::assertNotPushed(RunFileBackupTaskJob::class);
});

it('does not run the database job if a task is already running on the same remote server', function (): void {
    Queue::fake();

    $remoteServer = RemoteServer::factory()->create();

    $runningTask = BackupTask::factory()->create(['status' => 'running', 'type' => 'database', 'remote_server_id' => $remoteServer->id]);

    $task = BackupTask::factory()->create(['status' => 'ready', 'type' => 'database', 'remote_server_id' => $remoteServer->id]);

    $task->run();

    Queue::assertNotPushed(RunDatabaseBackupTaskJob::class);
});

it('does not run database backup task if the user account has been disabled', function (): void {
    Queue::fake();
    $disabledAccount = User::factory()->create(['account_disabled_at' => now()]);
    $remoteServer = RemoteServer::factory()->create(['user_id' => $disabledAccount->id]);
    $task = BackupTask::factory()->paused()->create(['status' => 'ready', 'type' => 'database', 'remote_server_id' => $remoteServer->id, 'user_id' => $disabledAccount->id]);

    $task->run();

    Queue::assertNotPushed(RunDatabaseBackupTaskJob::class);
});

it('does not run file backup task if the user account has been disabled', function (): void {
    Queue::fake();
    $disabledAccount = User::factory()->create(['account_disabled_at' => now()]);
    $remoteServer = RemoteServer::factory()->create(['user_id' => $disabledAccount->id]);
    $task = BackupTask::factory()->paused()->create(['status' => 'ready', 'type' => 'files', 'remote_server_id' => $remoteServer->id, 'user_id' => $disabledAccount->id]);

    $task->run();

    Queue::assertNotPushed(RunFileBackupTaskJob::class);
});

it('returns true if the type is files', function (): void {

    $task = BackupTask::factory()->create(['type' => 'files']);

    $this->assertTrue($task->isFilesType());
});

it('returns false if the type is not files', function (): void {

    $task = BackupTask::factory()->create(['type' => 'database']);

    $this->assertFalse($task->isFilesType());
});

it('returns true if the type is database', function (): void {

    $task = BackupTask::factory()->create(['type' => 'database']);

    $this->assertTrue($task->isDatabaseType());
});

it('returns false if the type is not database', function (): void {

    $task = BackupTask::factory()->create(['type' => 'files']);

    $this->assertFalse($task->isDatabaseType());
});

it('calculates the next run correctly', function (): void {
    $backupTask = BackupTask::factory()->create([
        'frequency' => 'daily',
        'time_to_run_at' => '05:30',
        'custom_cron_expression' => null,
    ]);

    $nextRun = $backupTask->calculateNextRun();

    expect($nextRun)->toBeInstanceOf(Carbon::class)
        ->and($nextRun->hour)->toBe(5)
        ->and($nextRun->minute)->toBe(30)
        ->and($nextRun->second)->toBe(0)
        ->and($nextRun->toDateString())->toBe(now()->addDay()->toDateString());
});

it('returns null if frequency is null and custom cron expression is null', function (): void {
    $backupTask = BackupTask::factory()->create([
        'frequency' => null,
        'time_to_run_at' => null,
        'custom_cron_expression' => null,
    ]);

    $nextRun = $backupTask->calculateNextRun();

    expect($nextRun)->toBeNull();
});

it('calculates next run from custom cron expression', function (): void {
    $backupTask = BackupTask::factory()->create([
        'custom_cron_expression' => '5 0 * 8 *',
        'frequency' => null,
        'time_to_run_at' => null,
    ]);

    $nextRun = $backupTask->calculateNextRun();

    expect($nextRun)->toBeInstanceOf(Carbon::class)
        ->and($nextRun->format('H:i'))->toBe('00:05')
        ->and($nextRun->month)->toBe(8);
});

it('returns the correct count of logs per month for the last six months', function (): void {
    $user = User::factory()->create();

    $backupTask = BackupTask::factory()->create([
        'frequency' => 'daily',
        'time_to_run_at' => '05:30',
        'custom_cron_expression' => null,
        'user_id' => $user->id,
    ]);

    $now = now()->startOfMonth();

    $expectedDates = [];
    for ($i = 1; $i <= 5; $i++) {
        $date = $now->copy()->subMonths($i)->startOfMonth();

        BackupTaskData::create([
            'duration' => 25,
            'backup_task_id' => $backupTask->id,
            'created_at' => $date,
        ]);
        $expectedDates[$date->format('M Y')] = 1;
    }

    $logsCountPerMonth = BackupTask::logsCountPerMonthForLastSixMonths($user->id);

    $monthsCount = count($logsCountPerMonth);
    expect($monthsCount)->toBe(5);

    foreach ($expectedDates as $month => $expectedCount) {
        expect($logsCountPerMonth)->toHaveKey($month)
            ->and($logsCountPerMonth[$month])->toBe($expectedCount);
    }

    $sortedMonths = array_keys($logsCountPerMonth);
    $sortedExpectedMonths = array_keys($expectedDates);
    sort($sortedMonths);
    sort($sortedExpectedMonths);
    expect($sortedMonths)->toBe($sortedExpectedMonths);
});

it('returns the backup tasks count per type', function (): void {
    $user = User::factory()->create();

    BackupTask::factory()->create([
        'user_id' => $user->id,
        'type' => BackupTask::TYPE_FILES,
    ]);

    BackupTask::factory()->create([
        'user_id' => $user->id,
        'type' => BackupTask::TYPE_DATABASE,
    ]);

    $backupTasksCountByType = BackupTask::backupTasksCountByType($user->id);

    expect($backupTasksCountByType)->toBe([
        'database' => 1,
        'files' => 1,
    ]);
});

it('returns true if the backup task is paused', function (): void {
    $task = BackupTask::factory()->paused()->create();

    expect($task->isPaused())->toBeTrue();
});

it('returns false if the backup task is not paused', function (): void {
    $task = BackupTask::factory()->create();

    expect($task->isPaused())->toBeFalse();
});

it('returns true if the backup task is favourited', function (): void {
    $task = BackupTask::factory()->favourited()->create();

    expect($task->isFavourited())->toBeTrue();
});

it('returns false if the backup task is not favourited', function (): void {
    $task = BackupTask::factory()->create();

    expect($task->isFavourited())->toBeFalse();
});

it('pauses the backup task', function (): void {
    $task = BackupTask::factory()->create();

    $task->pause();

    expect($task->isPaused())->toBeTrue();
});

it('unpauses the backup task', function (): void {
    $task = BackupTask::factory()->paused()->create();

    $task->resume();

    expect($task->isPaused())->toBeFalse();
});

it('favourites the backup task', function (): void {
    $task = BackupTask::factory()->create();

    $task->favourite();

    expect($task->isFavourited())->toBeTrue();
});

it('unfavourites the backup task', function (): void {
    $task = BackupTask::factory()->paused()->create();

    $task->unfavourite();

    expect($task->isFavourited())->toBeFalse();
});

it('scopes all the unpaused backup tasks', function (): void {

    $backupTaskOne = BackupTask::factory()->create();
    $backupTaskTwo = BackupTask::factory()->paused()->create();

    $tasks = BackupTask::notPaused()->get();

    expect($tasks->count())->toBe(1)
        ->and($tasks->first()->id)->toBe($backupTaskOne->id);

});

it('returns true if there is an appended file name', function (): void {
    $task = BackupTask::factory()->create(['appended_file_name' => 'test']);

    expect($task->hasFileNameAppended())->toBeTrue();
});

it('returns false if there is no appended file name', function (): void {
    $task = BackupTask::factory()->create(['appended_file_name' => null]);

    expect($task->hasFileNameAppended())->toBeFalse();
});

it('sets the last script update time', function (): void {
    $task = BackupTask::factory()->create();

    $task->setScriptUpdateTime();

    expect($task->last_script_update_at)->toBeInstanceOf(Carbon::class);
});

it('resets the last script update time', function (): void {
    $task = BackupTask::factory()->create(['last_script_update_at' => now()]);

    $task->resetScriptUpdateTime();

    expect($task->last_script_update_at)->toBeNull();
});

it('returns true if there is a notification stream email set', function (): void {
    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->email()->create();

    $task->notificationStreams()->attach($stream);

    expect($task->hasEmailNotification())->toBeTrue();
});

it('returns false if there is no notification stream email set', function (): void {
    $task = BackupTask::factory()->create();

    expect($task->hasEmailNotification())->toBeFalse();
});

it('returns true if there is a notification stream discord webhook set', function (): void {

    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->discord()->create();

    $task->notificationStreams()->attach($stream);

    expect($task->hasDiscordNotification())->toBeTrue();
});

it('returns false if there is no notification stream discord webhook set', function (): void {

    $task = BackupTask::factory()->create();

    expect($task->hasDiscordNotification())->toBeFalse();
});

it('returns true if there is a notification stream teams webhook set', function (): void {

    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->teams()->create();

    $task->notificationStreams()->attach($stream);

    expect($task->hasTeamNotification())->toBeTrue();
});

it('returns false if there is no notification stream teams webhook set', function (): void {

    $task = BackupTask::factory()->create();

    expect($task->hasTeamNotification())->toBeFalse();
});

it('returns true if there is a notification stream pushover set', function (): void {

    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->pushover()->create();

    $task->notificationStreams()->attach($stream);

    expect($task->hasPushoverNotification())->toBeTrue();
});

it('returns false if there is no notification stream pushover set', function (): void {

    $task = BackupTask::factory()->create();

    expect($task->hasPushoverNotification())->toBeFalse();
});

it('returns true if there is a notification stream telegram set', function (): void {
    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->telegram()->create();

    $task->notificationStreams()->attach($stream);

    expect($task->hasTelegramNotification())->toBeTrue();
});

it('returns false if there is no notification stream telegram set', function (): void {
    $task = BackupTask::factory()->create();

    expect($task->hasEmailNotification())->toBeFalse();
});

it('queues up a teams notification job if a teams notification has been set', function (): void {

    Queue::fake();

    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->teams()->create();
    $task->notificationStreams()->attach($stream);

    BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendNotifications();

    Queue::assertPushed(SendTeamsNotificationJob::class);
});

it('does not queue up a teams notification job if a teams notification has not been set', function (): void {

    Queue::fake();

    $task = BackupTask::factory()->create();

    $task->sendNotifications();

    Queue::assertNotPushed(SendTeamsNotificationJob::class);
});

it('queues up a pushover notification job if a pushover notification has been set', function (): void {

    Queue::fake();

    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->pushover()->create();
    $task->notificationStreams()->attach($stream);

    BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendNotifications();

    Queue::assertPushed(SendPushoverNotificationJob::class);
});

it('does not queue up a pushover notification job if a pushover notification has not been set', function (): void {

    Queue::fake();

    $task = BackupTask::factory()->create();

    $task->sendNotifications();

    Queue::assertNotPushed(SendPushoverNotificationJob::class);
});

it('queues up a discord notification job if a discord notification has been set', function (): void {

    Queue::fake();

    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->discord()->create();
    $task->notificationStreams()->attach($stream);

    BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendNotifications();

    Queue::assertPushed(SendDiscordNotificationJob::class);
});

it('does not queue up a discord notification job if a discord notification has not been set', function (): void {

    Queue::fake();

    $task = BackupTask::factory()->create();

    $task->sendNotifications();

    Queue::assertNotPushed(SendDiscordNotificationJob::class);
});

it('returns true if there is a notification slack webhook set', function (): void {

    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->slack()->create();

    $task->notificationStreams()->attach($stream);

    expect($task->hasSlackNotification())->toBeTrue();
});

it('returns false if there is no notification slack webhook set', function (): void {

    $task = BackupTask::factory()->create();

    expect($task->hasSlackNotification())->toBeFalse();
});

it('does not queue up a slack notification job if a slack notification has not been set', function (): void {
    Queue::fake();

    $task = BackupTask::factory()->create();

    $task->sendNotifications();

    Queue::assertNotPushed(SendSlackNotificationJob::class);
});

it('queues up a slack notification job if a slack notification has been set', function (): void {
    Queue::fake();

    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->slack()->create();

    $task->notificationStreams()->attach($stream);

    BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendNotifications();

    Queue::assertPushed(SendSlackNotificationJob::class);
});

it('queues up an email notification job if an email notification has been set', function (): void {

    Mail::fake();

    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->email()->create();

    $task->notificationStreams()->attach($stream);

    BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendNotifications();

    Mail::assertQueued(OutputMail::class);
});

it('does not queue up an email notification job if an email notification has not been set', function (): void {

    Mail::fake();

    $task = BackupTask::factory()->create();

    $task->sendNotifications();

    Mail::assertNotQueued(OutputMail::class);
});

it('queues up a telegram notification job if a telegram notification has been set', function (): void {

    Queue::fake();

    $task = BackupTask::factory()->create();
    $stream = NotificationStream::factory()->telegram()->create();
    $task->notificationStreams()->attach($stream);

    BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendNotifications();

    Queue::assertPushed(SendTelegramNotificationJob::class);
});

it('does not queue up a telegram notification job if a telegram notification has not been set', function (): void {

    Queue::fake();

    $task = BackupTask::factory()->create();

    $task->sendNotifications();

    Queue::assertNotPushed(SendTelegramNotificationJob::class);
});

it('can send multiple types of notifications simultaneously', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $discordStream = NotificationStream::factory()->discord()->create();
    $slackStream = NotificationStream::factory()->slack()->create();
    $teamsStream = NotificationStream::factory()->teams()->create();
    $emailStream = NotificationStream::factory()->email()->create();

    $task->notificationStreams()->attach([$discordStream->id, $slackStream->id, $emailStream->id, $teamsStream->id]);

    BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendNotifications();

    Queue::assertPushed(SendDiscordNotificationJob::class);
    Queue::assertPushed(SendSlackNotificationJob::class);
    Queue::assertPushed(SendTeamsNotificationJob::class);
    Mail::assertQueued(OutputMail::class);
});

it('does not send multiple types of notifications simultaneously if the user has quiet mode enabled', function (): void {
    Queue::fake();
    Mail::fake();

    $user = User::factory()->quietMode()->create();

    $task = BackupTask::factory()->create(['user_id' => $user->id]);
    $discordStream = NotificationStream::factory()->discord()->create(['user_id' => $user->id]);
    $slackStream = NotificationStream::factory()->slack()->create(['user_id' => $user->id]);
    $teamsStream = NotificationStream::factory()->teams()->create(['user_id' => $user->id]);
    $emailStream = NotificationStream::factory()->email()->create(['user_id' => $user->id]);

    $task->notificationStreams()->attach([$discordStream->id, $slackStream->id, $emailStream->id, $teamsStream->id]);

    BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendNotifications();

    Queue::assertNotPushed(SendDiscordNotificationJob::class);
    Queue::assertNotPushed(SendSlackNotificationJob::class);
    Queue::assertNotPushed(SendTeamsNotificationJob::class);
    Mail::assertNotQueued(OutputMail::class);

    $this->assertTrue($user->hasQuietMode());
});

it('sends a discord webhook successfully', function (): void {
    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);
    $discordURL = 'https://discord.com/api/webhooks/126608807316720786/T44a2qhUJuN6UJ92LJ2BPmh6sKt0kEaIm0QohQ8GyVSKoJOsWPtd4lQdCuLWOV6nqDcm';

    Http::fake([
        $discordURL => Http::response(null, 204),
    ]);

    $task->sendDiscordWebhookNotification($log, $discordURL);

    Http::assertSent(fn ($request): bool => $request->url() === $discordURL);
});

it('handles discord error response', function (): void {
    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);
    $discordURL = 'https://discord.com/api/webhooks/126608807316720786/T44a2qhUJuN6UJ92LJ2BPmh6sKt0kEaIm0QohQ8GyVSKoJOsWPtd4lQdCuLWOV6nqDcm';

    Http::fake([
        $discordURL => Http::response(['message' => 'Invalid Webhook Token'], 401),
    ]);

    expect(fn () => $task->sendDiscordWebhookNotification($log, $discordURL))
        ->toThrow(RuntimeException::class, 'Discord webhook failed: Invalid Webhook Token');
});

it('sends a slack webhook successfully', function (): void {
    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);
    $slackURL = 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX';

    Http::fake([
        $slackURL => Http::response('ok', 200),
    ]);

    $task->sendSlackWebhookNotification($log, $slackURL);

    Http::assertSent(fn ($request): bool => $request->url() === $slackURL);
});

it('handles slack error response', function (): void {
    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);
    $slackURL = 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX';

    Http::fake([
        $slackURL => Http::response('invalid_token', 403),
    ]);

    expect(fn () => $task->sendSlackWebhookNotification($log, $slackURL))
        ->toThrow(RuntimeException::class, 'Slack webhook failed: invalid_token');
});

it('sends a Teams webhook successfully', function (): void {
    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);
    $teamsURL = 'https://outlook.webhook.office.com/webhookb2/7a8b9c0d-1e2f-3g4h-5i6j-7k8l9m0n1o2p@a1b2c3d4-e5f6-g7h8-i9j0-k1l2m3n4o5p6/IncomingWebhook/qrstuvwxyz123456789/abcdef12-3456-7890-abcd-ef1234567890';

    Http::fake([
        $teamsURL => Http::response('', 200),
    ]);

    $task->sendTeamsWebhookNotification($log, $teamsURL);

    Http::assertSent(fn ($request): bool => $request->url() === $teamsURL);
});

it('handles Teams error response', function (): void {
    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);
    $teamsURL = 'https://outlook.webhook.office.com/webhookb2/7a8b9c0d-1e2f-3g4h-5i6j-7k8l9m0n1o2p@a1b2c3d4-e5f6-g7h8-i9j0-k1l2m3n4o5p6/IncomingWebhook/qrstuvwxyz123456789/abcdef12-3456-7890-abcd-ef1234567890';

    Http::fake([
        $teamsURL => Http::response('Unauthorized', 401),
    ]);

    expect(fn () => $task->sendTeamsWebhookNotification($log, $teamsURL))
        ->toThrow(RuntimeException::class, 'Teams webhook failed: Unauthorized');
});

it('sends a Pushover notification successfully', function (): void {
    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);
    $pushoverToken = 'abc123';
    $userToken = 'def456';

    Http::fake([
        'https://api.pushover.net/1/messages.json' => Http::response('', 200),
    ]);

    $task->sendPushoverNotification($log, $pushoverToken, $userToken);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.pushover.net/1/messages.json');
});

it('throws an exception when Pushover notification fails', function (): void {
    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);
    $pushoverToken = 'abc123';
    $userToken = 'def456';

    Http::fake([
        'https://api.pushover.net/1/messages.json' => Http::response('Error', 500),
    ]);

    expect(fn () => $task->sendPushoverNotification($log, $pushoverToken, $userToken))
        ->toThrow(RuntimeException::class, 'Pushover notification failed: Error');
});

it('sends a Telegram notification successfully', function (): void {
    $botToken = 'abc123';
    $userToken = 'def456';

    Config::set('services.telegram.bot_token', $botToken);

    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    Http::fake([
        "https://api.telegram.org/bot{$botToken}/sendMessage" => Http::response('', 200),
    ]);

    $task->sendTelegramNotification($log, $userToken);

    Http::assertSent(function (Request $request) use ($botToken, $userToken, $task, $log): bool {
        return $request->url() === "https://api.telegram.org/bot{$botToken}/sendMessage" &&
            $request['text'] === $this->composeTelegramNotificationText($task, $log) &&
            $request['chat_id'] === $userToken &&
            $request['parse_mode'] === 'HTML';
    });
});

it('throws an exception when Telegram notification fails', function (): void {
    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    Config::set('services.telegram.bot_token', '456');

    Http::fake([
        $this->getTelegramUrl() => Http::response('Error', 500),
    ]);

    expect(fn () => $task->sendTelegramNotification($log, 'fakeToken'))
        ->toThrow(RuntimeException::class, 'Telegram notification failed: Error');
});

it('throws an exception when Telegram bot token missing', function (): void {
    config()->set('services.telegram.bot_token', null);
    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    expect(fn () => $task->sendTelegramNotification($log, 'fakeToken'))
        ->toThrow(RuntimeException::class, 'Telegram bot token is not configured');
});

it('sends notifications based on backup task outcome and user preferences', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $successfulLog = BackupTaskLog::factory()->create([
        'backup_task_id' => $task->id,
        'successful_at' => now(),
    ]);

    $successOnlyDiscord = NotificationStream::factory()->discord()->successEnabled()->failureDisabled()->create();
    $failureOnlySlack = NotificationStream::factory()->slack()->successDisabled()->failureEnabled()->create();

    $task->notificationStreams()->saveMany([$successOnlyDiscord, $failureOnlySlack]);

    $task->sendNotifications();

    Queue::assertPushed(SendDiscordNotificationJob::class);
    Queue::assertNotPushed(SendSlackNotificationJob::class);
});

it('sends notifications for failed backups', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $failedLog = BackupTaskLog::factory()->create([
        'backup_task_id' => $task->id,
        'successful_at' => null,
    ]);

    $successOnlyDiscord = NotificationStream::factory()->discord()->successEnabled()->failureDisabled()->create();
    $failureOnlySlack = NotificationStream::factory()->slack()->successDisabled()->failureEnabled()->create();

    $task->notificationStreams()->saveMany([$successOnlyDiscord, $failureOnlySlack]);

    $task->sendNotifications();

    Queue::assertNotPushed(SendDiscordNotificationJob::class);
    Queue::assertPushed(SendSlackNotificationJob::class);
});

it('sends notifications based on backup task outcome and user preferences for successful backups', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $successfulLog = BackupTaskLog::factory()->create([
        'backup_task_id' => $task->id,
        'successful_at' => now(),
    ]);

    $successOnlyDiscord = NotificationStream::factory()->discord()->successEnabled()->failureDisabled()->create();
    $failureOnlySlack = NotificationStream::factory()->slack()->successDisabled()->failureEnabled()->create();
    $alwaysEmail = NotificationStream::factory()->email()->successEnabled()->failureEnabled()->create();
    $neverNotify = NotificationStream::factory()->discord()->successDisabled()->failureDisabled()->create();

    $task->notificationStreams()->saveMany([$successOnlyDiscord, $failureOnlySlack, $alwaysEmail, $neverNotify]);

    $task->sendNotifications();

    Queue::assertPushed(SendDiscordNotificationJob::class, 1);
    Queue::assertNotPushed(SendSlackNotificationJob::class);
    Mail::assertQueued(OutputMail::class, 1);
});

it('sends notifications based on backup task outcome and user preferences for failed backups', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $failedLog = BackupTaskLog::factory()->create([
        'backup_task_id' => $task->id,
        'successful_at' => null,
    ]);

    $successOnlyDiscord = NotificationStream::factory()->discord()->successEnabled()->failureDisabled()->create();
    $failureOnlySlack = NotificationStream::factory()->slack()->successDisabled()->failureEnabled()->create();
    $alwaysEmail = NotificationStream::factory()->email()->successEnabled()->failureEnabled()->create();
    $neverNotify = NotificationStream::factory()->discord()->successDisabled()->failureDisabled()->create();

    $task->notificationStreams()->saveMany([$successOnlyDiscord, $failureOnlySlack, $alwaysEmail, $neverNotify]);

    $task->sendNotifications();

    Queue::assertNotPushed(SendDiscordNotificationJob::class);
    Queue::assertPushed(SendSlackNotificationJob::class, 1);
    Mail::assertQueued(OutputMail::class, 1);
});

it('does not send notifications when there are no logs', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $allNotifications = NotificationStream::factory()->count(3)->create();

    $task->notificationStreams()->saveMany($allNotifications);

    $task->sendNotifications();

    Queue::assertNothingPushed();
    Mail::assertNothingQueued();
});

it('sends notifications for all enabled channels on successful backup', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $successfulLog = BackupTaskLog::factory()->create([
        'backup_task_id' => $task->id,
        'successful_at' => now(),
    ]);

    $discordSuccess = NotificationStream::factory()->discord()->successEnabled()->failureDisabled()->create();
    $slackBoth = NotificationStream::factory()->slack()->successEnabled()->failureEnabled()->create();
    $emailSuccess = NotificationStream::factory()->email()->successEnabled()->failureDisabled()->create();

    $task->notificationStreams()->saveMany([$discordSuccess, $slackBoth, $emailSuccess]);

    $task->sendNotifications();

    Queue::assertPushed(SendDiscordNotificationJob::class, 1);
    Queue::assertPushed(SendSlackNotificationJob::class, 1);
    Mail::assertQueued(OutputMail::class, 1);
});

it('sends notifications for all enabled channels on failed backup', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $failedLog = BackupTaskLog::factory()->create([
        'backup_task_id' => $task->id,
        'successful_at' => null,
    ]);

    $discordFailure = NotificationStream::factory()->discord()->successDisabled()->failureEnabled()->create();
    $slackBoth = NotificationStream::factory()->slack()->successEnabled()->failureEnabled()->create();
    $emailFailure = NotificationStream::factory()->email()->successDisabled()->failureEnabled()->create();

    $task->notificationStreams()->saveMany([$discordFailure, $slackBoth, $emailFailure]);

    $task->sendNotifications();

    Queue::assertPushed(SendDiscordNotificationJob::class, 1);
    Queue::assertPushed(SendSlackNotificationJob::class, 1);
    Mail::assertQueued(OutputMail::class, 1);
});

it('handles multiple notification streams of the same type correctly', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $successfulLog = BackupTaskLog::factory()->create([
        'backup_task_id' => $task->id,
        'successful_at' => now(),
    ]);

    $discord1 = NotificationStream::factory()->discord()->successEnabled()->failureDisabled()->create();
    $discord2 = NotificationStream::factory()->discord()->successEnabled()->failureEnabled()->create();
    $slack1 = NotificationStream::factory()->slack()->successEnabled()->failureDisabled()->create();
    $slack2 = NotificationStream::factory()->slack()->successDisabled()->failureEnabled()->create();

    $task->notificationStreams()->saveMany([$discord1, $discord2, $slack1, $slack2]);

    $task->sendNotifications();

    Queue::assertPushed(SendDiscordNotificationJob::class, 2);
    Queue::assertPushed(SendSlackNotificationJob::class, 1);
});

it('does not send notifications for disabled channels', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $successfulLog = BackupTaskLog::factory()->create([
        'backup_task_id' => $task->id,
        'successful_at' => now(),
    ]);

    $disabledDiscord = NotificationStream::factory()->discord()->successDisabled()->failureDisabled()->create();
    $disabledSlack = NotificationStream::factory()->slack()->successDisabled()->failureDisabled()->create();
    $disabledEmail = NotificationStream::factory()->email()->successDisabled()->failureDisabled()->create();

    $task->notificationStreams()->saveMany([$disabledDiscord, $disabledSlack, $disabledEmail]);

    $task->sendNotifications();

    Queue::assertNothingPushed();
    Mail::assertNothingQueued();
});

it('sends correct notification for each channel when all are enabled', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $successfulLog = BackupTaskLog::factory()->create([
        'backup_task_id' => $task->id,
        'successful_at' => now(),
    ]);

    $discordBoth = NotificationStream::factory()->discord()->successEnabled()->failureEnabled()->create();
    $slackBoth = NotificationStream::factory()->slack()->successEnabled()->failureEnabled()->create();
    $emailBoth = NotificationStream::factory()->email()->successEnabled()->failureEnabled()->create();

    $task->notificationStreams()->saveMany([$discordBoth, $slackBoth, $emailBoth]);

    $task->sendNotifications();

    Queue::assertPushed(SendDiscordNotificationJob::class, 1);
    Queue::assertPushed(SendSlackNotificationJob::class, 1);
    Mail::assertQueued(OutputMail::class, 1);
});

it('handles tasks with no notification streams correctly', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    BackupTaskLog::factory()->create([
        'backup_task_id' => $task->id,
        'successful_at' => now(),
    ]);

    $task->sendNotifications();

    Queue::assertNothingPushed();
    Mail::assertNothingQueued();
});

it('correctly determines if a notification should be sent', function (): void {
    $task = BackupTask::factory()->create();

    $successOnlyStream = NotificationStream::factory()->successEnabled()->failureDisabled()->create();
    $failureOnlyStream = NotificationStream::factory()->successDisabled()->failureEnabled()->create();

    expect($task->shouldSendNotification($successOnlyStream, true))->toBeTrue()
        ->and($task->shouldSendNotification($successOnlyStream, false))->toBeFalse()
        ->and($task->shouldSendNotification($failureOnlyStream, true))->toBeFalse()
        ->and($task->shouldSendNotification($failureOnlyStream, false))->toBeTrue();
});

it('dispatches the correct notification type', function (): void {
    Queue::fake();
    Mail::fake();

    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $discordStream = NotificationStream::factory()->discord()->create();
    $slackStream = NotificationStream::factory()->slack()->create();
    $emailStream = NotificationStream::factory()->email()->create();

    $task->dispatchNotification($discordStream, $log, 'default');
    $task->dispatchNotification($slackStream, $log, 'default');
    $task->dispatchNotification($emailStream, $log, 'default');

    Queue::assertPushed(SendDiscordNotificationJob::class);
    Queue::assertPushed(SendSlackNotificationJob::class);
    Mail::assertQueued(OutputMail::class);
});

it('throws an exception for unsupported notification type', function (): void {
    $task = BackupTask::factory()->create();
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);
    $invalidStream = NotificationStream::factory()->create(['type' => 'invalid']);

    expect(fn () => $task->dispatchNotification($invalidStream, $log, 'default'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported notification type: invalid');
});

it('returns true if there is a store path specified', function (): void {

    $task = BackupTask::factory()->create(['store_path' => 'path/to/store']);

    expect($task->hasCustomStorePath())->toBeTrue();
});

it('returns false if there is no store path specified', function (): void {

    $task = BackupTask::factory()->create(['store_path' => null]);

    expect($task->hasCustomStorePath())->toBeFalse();
});

it('returns true if a remote server has another task that is running already', function (): void {

    $remoteServer = RemoteServer::factory()->create();
    $task1 = BackupTask::factory()->create(['status' => 'running', 'remote_server_id' => $remoteServer->id]);
    $task2 = BackupTask::factory()->create(['status' => 'ready', 'remote_server_id' => $remoteServer->id]);

    expect($task2->isAnotherTaskRunningOnSameRemoteServer())->toBeTrue();
});

it('returns false if a remote server does not have another task that is running already', function (): void {

    $remoteServer = RemoteServer::factory()->create();
    $task1 = BackupTask::factory()->create(['status' => 'ready', 'remote_server_id' => $remoteServer->id]);
    $task2 = BackupTask::factory()->create(['status' => 'ready', 'remote_server_id' => $remoteServer->id]);

    expect($task2->isAnotherTaskRunningOnSameRemoteServer())->toBeFalse();
});

it('returns false if there is only one task on the remote server', function (): void {

    $remoteServer = RemoteServer::factory()->create();
    $task = BackupTask::factory()->create(['status' => 'running', 'remote_server_id' => $remoteServer->id]);

    expect($task->isAnotherTaskRunningOnSameRemoteServer())->toBeFalse();
});

it('returns null if there are no attached tags', function (): void {

    $task = BackupTask::factory()->create();

    expect($task->listOfAttachedTagLabels())->toBeNull();
});

it('returns the attached tags as a string', function (): void {

    $task = BackupTask::factory()->create();
    $tag1 = Tag::factory()->create(['label' => 'Tag 1']);
    $tag2 = Tag::factory()->create(['label' => 'Tag 2']);

    $task->tags()->attach([$tag1->id, $tag2->id]);

    expect($task->listOfAttachedTagLabels())->toBe('Tag 1, Tag 2');
});

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::create(2024, 6, 12, 18, 57));
});

it('formats last run correctly for Danish locale', function (): void {
    $user = User::factory()->create(['language' => 'da', 'timezone' => 'UTC']);
    $backupTask = BackupTask::factory()->create(['last_run_at' => now()]);

    $result = $backupTask->lastRunFormatted($user);

    expect($result)->toBe('12 juni 2024 18:57');
});

it('formats last run correctly for English locale', function (): void {
    $user = User::factory()->create(['language' => 'en', 'timezone' => 'UTC']);
    $backupTask = BackupTask::factory()->create(['last_run_at' => now()]);

    $result = $backupTask->lastRunFormatted($user);

    expect($result)->toBe('12 June 2024 18:57');
});

it('returns null when last run is null', function (): void {
    $user = User::factory()->create();
    $backupTask = BackupTask::factory()->create(['last_run_at' => null]);

    $result = $backupTask->lastRunFormatted($user);

    expect($result)->toBe(null);
});

it('formats last run correctly for authenticated user', function (): void {
    $user = User::factory()->create(['language' => 'da', 'timezone' => 'Europe/Copenhagen']);
    Auth::login($user);

    $backupTask = BackupTask::factory()->create(['last_run_at' => now()]);

    $result = $backupTask->lastRunFormatted();

    expect($result)->toBe('12 juni 2024 20:57');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('formats time correctly for UTC timezone', function (): void {
    $user = User::factory()->create(['timezone' => 'UTC']);
    $backupTask = BackupTask::factory()->create(['time_to_run_at' => '04:45']);

    $result = $backupTask->runTimeFormatted();

    expect($result)->toBe('04:45')
        ->and($user->timezone)->toBe('UTC');
});

it('correctly adjusts time for London timezone', function (): void {
    $user = User::factory()->create(['timezone' => 'Europe/London']);
    Auth::login($user);

    $backupTask = BackupTask::factory()->create(['time_to_run_at' => '04:15']);

    Config::set('app.timezone', 'UTC');

    Carbon::setTestNow(Carbon::create(2024, 7, 16, 4, 15, 0, 'UTC'));

    $result = $backupTask->runTimeFormatted();

    expect($result)->toBe('05:15')
        ->and($user->timezone)->toBe('Europe/London');
});

it('handles non-DST period correctly', function (): void {
    $user = User::factory()->create(['timezone' => 'America/New_York']);
    Auth::login($user);
    $backupTask = BackupTask::factory()->create(['time_to_run_at' => '04:45']);

    Carbon::setTestNow(Carbon::create(2023, 1, 15, 4, 45, 0, 'UTC'));

    $result = $backupTask->runTimeFormatted();

    expect($result)->toBe('23:45')
        ->and($user->timezone)->toBe('America/New_York');
});

it('handles null user correctly', function (): void {
    Auth::logout();
    $backupTask = BackupTask::factory()->create(['time_to_run_at' => '04:45']);

    Config::set('app.timezone', 'UTC');

    $result = $backupTask->runTimeFormatted();

    expect($result)->toBe('04:45');
});

it('handles time around midnight correctly', function (): void {
    $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
    Auth::login($user);
    $backupTask = BackupTask::factory()->create(['time_to_run_at' => '23:45']);

    Carbon::setTestNow(Carbon::create(2023, 7, 15, 23, 45, 0, 'UTC'));

    $result = $backupTask->runTimeFormatted();

    expect($result)->toBe('16:45')
        ->and($user->timezone)->toBe('America/Los_Angeles');
});

it('handles explicit user parameter', function (): void {
    $user1 = User::factory()->create(['timezone' => 'America/New_York']);
    $user2 = User::factory()->create(['timezone' => 'Europe/London']);
    Auth::login($user1);
    $backupTask = BackupTask::factory()->create(['time_to_run_at' => '12:00']);

    Carbon::setTestNow(Carbon::create(2023, 7, 15, 12, 0, 0, 'UTC'));

    $result = $backupTask->runTimeFormatted($user2);

    expect($result)->toBe('13:00')
        ->and($user2->timezone)->toBe('Europe/London');
});

it('returns null if the time to run at is null', function (): void {

    $backupTask = BackupTask::factory()->create(['time_to_run_at' => null]);

    expect($backupTask->runTimeFormatted())->toBe(null);
});

it('retrieves backup tasks data for the past 90 days', function (): void {
    Carbon::setTestNow('2023-07-30');

    // Create some test data
    BackupTask::factory()->count(5)->create([
        'type' => 'Files',
        'created_at' => now()->subDays(10),
    ]);
    BackupTask::factory()->count(3)->create([
        'type' => 'Database',
        'created_at' => now()->subDays(5),
    ]);

    $result = BackupTask::getBackupTasksData();

    expect($result)->toHaveKeys(['backupDates', 'fileBackupCounts', 'databaseBackupCounts'])
        ->and($result['backupDates'])->toHaveCount(90)
        ->and($result['fileBackupCounts'])->toHaveCount(90)
        ->and($result['databaseBackupCounts'])->toHaveCount(90)
        ->and($result['fileBackupCounts'][79])->toBe(5) // 10 days ago
        ->and($result['databaseBackupCounts'][84])->toBe(3); // 5 days ago

    Carbon::setTestNow(); // Reset the mock
});

it('retrieves backup success rate data only for months with data', function (): void {
    Carbon::setTestNow('2023-07-30');

    $testMonths = [
        now()->subMonths(2)->startOfMonth(),
        now()->subMonths(3)->startOfMonth(),
        now()->subMonths(4)->startOfMonth(),
    ];

    foreach ($testMonths as $date) {
        BackupTaskLog::factory()->count(8)->create([
            'created_at' => $date,
            'successful_at' => $date,
        ]);
        BackupTaskLog::factory()->count(2)->create([
            'created_at' => $date,
            'successful_at' => null,
        ]);
    }

    $result = BackupTask::getBackupSuccessRateData();

    expect($result)->toHaveKeys(['labels', 'data'])
        ->and($result['labels'])->toHaveCount(3)
        ->and($result['data'])->toHaveCount(3);

    $expectedLabels = array_map(fn ($date): string => $date->format('Y-m'), array_reverse($testMonths));
    expect($result['labels'])->toBe($expectedLabels)
        ->and($result['data'])->each(fn ($rate) => $rate->toBe(80.0));

    Carbon::setTestNow();
});

it('retrieves average backup size data', function (): void {
    $fileTasks = BackupTask::factory()->count(3)->create(['type' => 'Files']);
    $dbTasks = BackupTask::factory()->count(2)->create(['type' => 'Database']);

    foreach ($fileTasks as $fileTask) {
        BackupTaskData::create([
            'backup_task_id' => $fileTask->getAttribute('id'),
            'size' => 1500000, // 1.5 MB
            'duration' => 100000,
        ]);
        BackupTaskLog::factory()->create([
            'backup_task_id' => $fileTask->getAttribute('id'),
            'successful_at' => now(),
        ]);
    }

    foreach ($dbTasks as $dbTask) {
        BackupTaskData::create([
            'backup_task_id' => $dbTask->getAttribute('id'),
            'size' => 750000, // 750 KB
            'duration' => 100000,
        ]);
        BackupTaskLog::factory()->create([
            'backup_task_id' => $dbTask->getAttribute('id'),
            'successful_at' => now(),
        ]);
    }

    $result = BackupTask::getAverageBackupSizeData();

    expect($result)->toHaveKeys(['labels', 'data'])
        ->and($result['labels'])->toBe(['Files', 'Database']);

    $expectedFileSize = Number::fileSize(1500000);
    $expectedDbSize = Number::fileSize(750000);

    expect($result['data'][0])->toBe($expectedFileSize)
        ->and($result['data'][1])->toBe($expectedDbSize)
        ->and($result['data'][0])->toMatch('/^\d+(\.\d+)?\s(B|KB|MB|GB|TB)$/')
        ->and($result['data'][1])->toMatch('/^\d+(\.\d+)?\s(B|KB|MB|GB|TB)$/');
});

it('retrieves completion time data for the past 3 months', function (): void {
    Carbon::setTestNow('2023-07-30');

    // Create some test data
    $tasks = BackupTask::factory()->count(5)->create();

    foreach ($tasks as $index => $task) {
        BackupTaskData::create([
            'backup_task_id' => $task->getAttribute('id'),
            'duration' => ($index + 1) * 60, // 1-5 minutes
        ]);
        BackupTaskLog::factory()->create([
            'backup_task_id' => $task->getAttribute('id'),
            'created_at' => now()->subDays($index * 15), // Spread over 2 months
            'successful_at' => now()->subDays($index * 15),
        ]);
    }

    $result = BackupTask::getCompletionTimeData();

    expect($result)->toHaveKeys(['labels', 'data'])
        ->and($result['labels'])->toHaveCount(5)
        ->and($result['data'])->toHaveCount(5)
        ->and($result['data'][0])->toBe(5.0) // 5 minutes
        ->and($result['data'][4])->toBe(1.0); // 1 minute

    Carbon::setTestNow();
});

it('correctly formats file sizes in getAverageBackupSizeData method', function (): void {
    $sizesAndExpectedFormats = [
        500 => '500 B',
        1024 => '1 KB',
        1500 => '1 KB',
        1048576 => '1 MB',
        1500000 => '1 MB',
        1073741824 => '1 GB',
        1500000000 => '1 GB',
    ];

    foreach ($sizesAndExpectedFormats as $size => $expectedFormat) {
        $task = BackupTask::factory()->create(['type' => 'Test']);

        BackupTaskData::create([
            'backup_task_id' => $task->id,
            'size' => $size,
            'duration' => 100000,
        ]);
        BackupTaskLog::factory()->create([
            'backup_task_id' => $task->id,
            'successful_at' => now(),
        ]);

        $result = BackupTask::getAverageBackupSizeData();

        $actualFormat = $result['data'][0];

        // Check if the unit (B, KB, MB, GB) is correct
        expect($actualFormat)->toContain(explode(' ', $expectedFormat)[1]);

        // Check if the numeric part is close to what we expect
        $actualNumber = (float) explode(' ', $actualFormat)[0];
        $expectedNumber = (float) explode(' ', $expectedFormat)[0];
        expect($actualNumber)->toBeGreaterThanOrEqual($expectedNumber * 0.9)
            ->toBeLessThanOrEqual($expectedNumber * 1.1);

        // Clean up for the next iteration
        BackupTask::query()->delete();
        BackupTaskData::query()->delete();
        BackupTaskLog::query()->delete();
    }
});

it('returns true if an encryption password is set', function (): void {

    $task = BackupTask::factory()->create([
        'encryption_password' => 'password123',
    ]);

    $this->assertTrue($task->hasEncryptionPassword());
});

it('returns false if an encryption password is not set', function (): void {

    $task = BackupTask::factory()->create([
        'encryption_password' => null,
    ]);

    $this->assertFalse($task->hasEncryptionPassword());
});

it('generates a webhook token on creation', function (): void {
    $task = BackupTask::factory()->create();

    expect($task->webhook_token)->not->toBeNull()
        ->and(strlen($task->webhook_token))->toBe(64);
});

it('refreshes the webhook token', function (): void {
    $task = BackupTask::factory()->create();
    $originalToken = $task->webhook_token;

    $newToken = $task->refreshWebhookToken();

    expect($newToken)->not->toBe($originalToken)
        ->and($task->webhook_token)->toBe($newToken)
        ->and(strlen((string) $newToken))->toBe(64);
});

it('generates unique webhook tokens for multiple tasks', function (): void {
    $tasks = BackupTask::factory()->count(5)->create();
    $tokens = $tasks->pluck('webhook_token')->toArray();

    expect(count($tokens))->toBe(5)
        ->and(count(array_unique($tokens)))->toBe(5);
});

it('ensures webhook tokens are added to existing models', function (): void {
    $task = BackupTask::factory()->make(['webhook_token' => null]);
    $task->saveQuietly();

    $retrievedTask = BackupTask::find($task->id);

    expect($retrievedTask->webhook_token)->not->toBeNull()
        ->and(strlen($retrievedTask->webhook_token))->toBe(64);
});

it('generates the correct webhook url', function (): void {
    $task = BackupTask::factory()->create(['id' => 123]);
    $token = $task->webhook_token;

    $expectedUrl = url("/webhooks/backup-tasks/{$task->getKey()}/run?token={$token}");

    expect($task->webhook_url)->toBe($expectedUrl);
});

it('generates a unique token when refreshing even if collision would occur', function (): void {
    $task1 = BackupTask::factory()->create();
    $task2 = BackupTask::factory()->create();

    // Force a potential collision by setting both tasks to have the same token
    $commonToken = 'same-token-for-both-tasks-' . Str::random(10);
    $task1->webhook_token = $commonToken;
    $task1->saveQuietly();
    $task2->webhook_token = $commonToken;
    $task2->saveQuietly();

    // Verify they have the same token initially
    expect($task1->webhook_token)->toBe($task2->webhook_token);

    // Refresh task1's token
    $newToken = $task1->refreshWebhookToken();

    expect($newToken)->not->toBe($commonToken)
        ->and($task1->webhook_token)->not->toBe($task2->webhook_token);
});

it('sets the time when the run webhook was last used', function (): void {

    $task = BackupTask::factory()->create([
        'run_webhook_last_used_at' => null,
    ]);

    $this->assertNull($task->getAttribute('run_webhook_last_used_at'));

    $task->setRunWebhookTime();

    $this->assertNotNull($task->getAttribute('run_webhook_last_used_at'));
});

it('returns true if the task has an associated prescript', function (): void {
    $task = BackupTask::factory()->create();

    $task->scripts()->attach(Script::factory()->prescript()->create());

    $this->assertTrue($task->hasPrescript());
});

it('returns false if the task does not have an associated prescript', function (): void {
    $task = BackupTask::factory()->create();

    $this->assertFalse($task->hasPrescript());

    $this->assertCount(0, $task->scripts);
});

it('returns true if the task does have an associated postscript', function (): void {
    $task = BackupTask::factory()->create();

    $task->scripts()->attach(Script::factory()->postscript()->create());

    $this->assertTrue($task->hasPostscript());
});

it('returns false if the task does not have an associated postscript', function (): void {
    $task = BackupTask::factory()->create();

    $this->assertFalse($task->hasPostscript());

    $this->assertCount(0, $task->scripts);
});

it('outputs the correct backup size data values for the chart', function (): void {
    $user = User::factory()->create();

    $backupTaskOne = BackupTask::factory()->create([
        'user_id' => $user->getAttributeValue('id'),
        'label' => 'Database Backup',
        'type' => 'database',
    ]);

    $backupTaskOne->data()->create([
        'size' => '143434',
        'duration' => '120',
    ]);

    $backupTaskTwo = BackupTask::factory()->create([
        'user_id' => $user->getAttributeValue('id'),
        'label' => 'File Backup',
        'type' => 'files',
    ]);

    $backupTaskTwo->data()->create([
        'size' => '287868',
        'duration' => '120',
    ]);

    $result = BackupTask::backupSizeByTypeData($user->getAttributeValue('id'), 2);

    expect($result)->toBeArray()
        ->toHaveKeys(['labels', 'datasets', 'formatted'])
        ->and($result['labels'])->toBeArray()
        ->toContain('Database Backup')
        ->toContain('File Backup')
        ->and($result['datasets'])->toBeArray()->toHaveCount(1)
        ->and($result['datasets'][0]['label'])->toBe(__('Average Backup Size'))
        ->and($result['datasets'][0]['data'])->toBeArray()->toHaveCount(2)
        ->and($result['datasets'][0]['data'])->toContain(143434)
        ->toContain(287868)
        ->and($result['formatted'])->toBeArray()->toHaveCount(2)
        ->and($result['formatted'])->toContain(Number::fileSize(143434))
        ->toContain(Number::fileSize(287868));
});
