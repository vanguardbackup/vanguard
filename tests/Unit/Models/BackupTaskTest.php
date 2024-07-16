<?php

declare(strict_types=1);

use App\Jobs\BackupTasks\SendDiscordNotificationJob;
use App\Jobs\BackupTasks\SendSlackNotificationJob;
use App\Jobs\RunDatabaseBackupTaskJob;
use App\Jobs\RunFileBackupTaskJob;
use App\Mail\BackupTasks\OutputMail;
use App\Models\BackupTask;
use App\Models\BackupTaskData;
use App\Models\BackupTaskLog;
use App\Models\RemoteServer;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Carbon;

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

it('returns true if there is a notification email set', function (): void {

    $task = BackupTask::factory()->create(['notify_email' => 'alerts@email.com']);

    expect($task->hasNotifyEmail())->toBeTrue();
});

it('returns false if there is no notification email set', function (): void {

    $task = BackupTask::factory()->create(['notify_email' => null]);

    expect($task->hasNotifyEmail())->toBeFalse();
});

it('returns true if there is a notification discord webhook set', function (): void {

    $task = BackupTask::factory()->create(['notify_discord_webhook' => 'https://discord.com/webhook']);

    expect($task->hasNotifyDiscordWebhook())->toBeTrue();
});

it('returns false if there is no notification discord webhook set', function (): void {

    $task = BackupTask::factory()->create(['notify_discord_webhook' => null]);

    expect($task->hasNotifyDiscordWebhook())->toBeFalse();
});

it('queues up a discord notification job if a discord notification has been set', function (): void {

    Queue::fake();

    $task = BackupTask::factory()->create(['notify_discord_webhook' => 'https://discord.com/webhook']);
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendNotifications();

    Queue::assertPushed(SendDiscordNotificationJob::class);
});

it('does not queue up a discord notification job if a discord notification has not been set', function (): void {

    Queue::fake();

    $task = BackupTask::factory()->create(['notify_discord_webhook' => null]);

    $task->sendNotifications();

    Queue::assertNotPushed(SendDiscordNotificationJob::class);
});

it('returns true if there is a notification slack webhook set', function (): void {

    $task = BackupTask::factory()->create(['notify_slack_webhook' => 'https://slack.com/webhook']);

    expect($task->hasNotifySlackWebhook())->toBeTrue();
});

it('returns false if there is no notification slack webhook set', function (): void {

    $task = BackupTask::factory()->create(['notify_slack_webhook' => null]);

    expect($task->hasNotifySlackWebhook())->toBeFalse();
});

it('does not queue up a slack notification job if a slack notification has not been set', function (): void {
    Queue::fake();

    $task = BackupTask::factory()->create(['notify_slack_webhook' => null]);

    $task->sendNotifications();

    Queue::assertNotPushed(SendSlackNotificationJob::class);
});

it('queues up a slack notification job if a slack notification has been set', function (): void {
    Queue::fake();

    $task = BackupTask::factory()->create(['notify_slack_webhook' => 'https://slack.com/webhook']);
    BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendNotifications();

    Queue::assertPushed(SendSlackNotificationJob::class);
});

it('queues up an email notification job if an email notification has been set', function (): void {

    Mail::fake();

    $task = BackupTask::factory()->create(['notify_email' => 'alerts@email.com']);
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendNotifications();

    Mail::assertQueued(OutputMail::class);
});

it('does not queue up an email notification job if an email notification has not been set', function (): void {

    Mail::fake();

    $task = BackupTask::factory()->create(['notify_email' => null]);

    $task->sendNotifications();

    Mail::assertNotQueued(OutputMail::class);
});

it('queues up an email notification', function (): void {
    Mail::fake();
    $user = User::factory()->create();
    $task = BackupTask::factory()->create(['notify_email' => $user->email]);
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);
    $task->sendEmailNotification($log);

    Mail::assertQueued(OutputMail::class);
});

it('sends a discord webhook', function (): void {
    Http::fake();

    $task = BackupTask::factory()->create(['notify_discord_webhook' => 'https://discord.com/webhook']);
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendDiscordWebhookNotification($log);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://discord.com/webhook');
});

it('sends a slack webhook', function (): void {
    Http::fake();

    $task = BackupTask::factory()->create(['notify_slack_webhook' => 'https://slack.com/webhook']);
    $log = BackupTaskLog::factory()->create(['backup_task_id' => $task->id]);

    $task->sendSlackWebhookNotification($log);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://slack.com/webhook');
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

it('returns true if the isolated credentials are set', function (): void {

    $task = BackupTask::factory()->create([
        'isolated_username' => 'john_doe',
        'isolated_password' => 'password123',
    ]);

    $this->assertTrue($task->hasIsolatedCredentials());
});

it('returns false if the isolated credentials are set', function (): void {

    $task = BackupTask::factory()->create([
        'isolated_username' => null,
        'isolated_password' => null,
    ]);

    $this->assertFalse($task->hasIsolatedCredentials());
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

it('returns "Never" when last run is null', function (): void {
    $user = User::factory()->create();
    $backupTask = BackupTask::factory()->create(['last_run_at' => null]);

    $result = $backupTask->lastRunFormatted($user);

    expect($result)->toBe('Never');
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
