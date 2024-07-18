<?php

declare(strict_types=1);

use App\Console\Commands\SendSummaryBackupTaskEmails;
use App\Mail\User\SummaryBackupMail;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
});

it('sends summary emails to opted-in users with backup tasks', function (): void {
    $user = User::factory()->receivesWeeklySummaries()->create();
    $backupTask = BackupTask::factory()->create(['user_id' => $user->id]);

    $lastMonday = Carbon::now()->subWeek()->startOfWeek();
    $lastFriday = $lastMonday->copy()->endOfWeek()->subDays(2);

    BackupTaskLog::factory()->count(3)->create([
        'backup_task_id' => $backupTask->id,
        'created_at' => $lastMonday->addDay(),
        'successful_at' => $lastMonday->addDay(),
    ]);

    BackupTaskLog::factory()->count(2)->create([
        'backup_task_id' => $backupTask->id,
        'created_at' => $lastFriday,
        'successful_at' => null,
    ]);

    $this->artisan(SendSummaryBackupTaskEmails::class)
        ->expectsOutputToContain('Beginning to gather data to send summary emails.')
        ->expectsOutputToContain('Sent summary emails to 1 opted-in users.')
        ->assertExitCode(0);

    Mail::assertQueued(SummaryBackupMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('does not send emails when there are no opted-in users', function (): void {
    User::factory()->doesNotReceiveWeeklySummaries()->create();

    $this->artisan(SendSummaryBackupTaskEmails::class)
        ->expectsOutputToContain('Beginning to gather data to send summary emails.')
        ->expectsOutputToContain('No users opted in to receive summary emails.')
        ->assertExitCode(0);

    Mail::assertNothingQueued();
});

it('does not send emails to users without backup tasks', function (): void {
    User::factory()->receivesWeeklySummaries()->create();

    $this->artisan(SendSummaryBackupTaskEmails::class)
        ->expectsOutputToContain('Beginning to gather data to send summary emails.')
        ->expectsOutputToContain('Sent summary emails to 0 opted-in users.')
        ->assertExitCode(0);

    Mail::assertNothingQueued();
});

it('sends emails only for backup tasks within the specified date range', function (): void {
    $user = User::factory()->receivesWeeklySummaries()->create();
    $backupTask = BackupTask::factory()->create(['user_id' => $user->id]);

    $lastSunday = Carbon::now()->subWeek()->endOfWeek();
    $lastMonday = $lastSunday->copy()->subDays(6);
    $lastFriday = $lastSunday->copy()->subDays(2);
    $thisSaturday = Carbon::now()->startOfWeek()->subDays(2);

    // Tasks within range
    BackupTaskLog::factory()->count(2)->create([
        'backup_task_id' => $backupTask->id,
        'created_at' => $lastMonday,
        'successful_at' => $lastMonday,
    ]);

    BackupTaskLog::factory()->create([
        'backup_task_id' => $backupTask->id,
        'created_at' => $lastFriday,
        'successful_at' => null,
    ]);

    // Tasks outside range
    BackupTaskLog::factory()->create([
        'backup_task_id' => $backupTask->id,
        'created_at' => $lastSunday,
        'successful_at' => $lastSunday,
    ]);

    BackupTaskLog::factory()->create([
        'backup_task_id' => $backupTask->id,
        'created_at' => $thisSaturday,
        'successful_at' => $thisSaturday,
    ]);

    $this->artisan(SendSummaryBackupTaskEmails::class)
        ->expectsOutputToContain('Beginning to gather data to send summary emails.')
        ->expectsOutputToContain('Sent summary emails to 1 opted-in users.')
        ->assertExitCode(0);

    Mail::assertQueued(SummaryBackupMail::class, function ($mail) use ($user): bool {
        $data = $mail->data;

        return $mail->hasTo($user->email) &&
            $data['total_tasks'] === 3 &&
            $data['successful_tasks'] === 2 &&
            $data['failed_tasks'] === 1;
    });
});

it('handles multiple users with varying backup task counts', function (): void {
    $user1 = User::factory()->receivesWeeklySummaries()->create();
    $user2 = User::factory()->receivesWeeklySummaries()->create();
    $user3 = User::factory()->receivesWeeklySummaries()->create();

    $lastMonday = Carbon::now()->subWeek()->startOfWeek();

    // User 1 has 3 successful tasks
    $backupTask1 = BackupTask::factory()->create(['user_id' => $user1->id]);
    BackupTaskLog::factory()->count(3)->create([
        'backup_task_id' => $backupTask1->id,
        'created_at' => $lastMonday,
        'successful_at' => $lastMonday,
    ]);

    // User 2 has 2 failed tasks
    $backupTask2 = BackupTask::factory()->create(['user_id' => $user2->id]);
    BackupTaskLog::factory()->count(2)->create([
        'backup_task_id' => $backupTask2->id,
        'created_at' => $lastMonday,
        'successful_at' => null,
    ]);

    // User 3 has no tasks

    $this->artisan(SendSummaryBackupTaskEmails::class)
        ->expectsOutputToContain('Beginning to gather data to send summary emails.')
        ->expectsOutputToContain('Sent summary emails to 2 opted-in users.')
        ->assertExitCode(0);

    Mail::assertQueued(SummaryBackupMail::class, 2);
    Mail::assertQueued(SummaryBackupMail::class, function ($mail) use ($user1): bool {
        $data = $mail->data;

        return $mail->hasTo($user1->email) &&
            $data['total_tasks'] === 3 &&
            $data['successful_tasks'] === 3 &&
            $data['failed_tasks'] === 0;
    });
    Mail::assertQueued(SummaryBackupMail::class, function ($mail) use ($user2): bool {
        $data = $mail->data;

        return $mail->hasTo($user2->email) &&
            $data['total_tasks'] === 2 &&
            $data['successful_tasks'] === 0 &&
            $data['failed_tasks'] === 2;
    });
});

it('respects user preferences for receiving weekly summaries', function (): void {
    $optedInUser = User::factory()->receivesWeeklySummaries()->create();
    $optedOutUser = User::factory()->doesNotReceiveWeeklySummaries()->create();

    $backupTask1 = BackupTask::factory()->create(['user_id' => $optedInUser->id]);
    $backupTask2 = BackupTask::factory()->create(['user_id' => $optedOutUser->id]);

    $lastMonday = Carbon::now()->subWeek()->startOfWeek();

    BackupTaskLog::factory()->create([
        'backup_task_id' => $backupTask1->id,
        'created_at' => $lastMonday,
        'successful_at' => $lastMonday,
    ]);

    BackupTaskLog::factory()->create([
        'backup_task_id' => $backupTask2->id,
        'created_at' => $lastMonday,
        'successful_at' => $lastMonday,
    ]);

    $this->artisan(SendSummaryBackupTaskEmails::class)
        ->expectsOutputToContain('Beginning to gather data to send summary emails.')
        ->expectsOutputToContain('Sent summary emails to 1 opted-in users.')
        ->assertExitCode(0);

    Mail::assertQueued(SummaryBackupMail::class, 1);
    Mail::assertQueued(SummaryBackupMail::class, function ($mail) use ($optedInUser) {
        return $mail->hasTo($optedInUser->email);
    });
    Mail::assertNotQueued(SummaryBackupMail::class, function ($mail) use ($optedOutUser) {
        return $mail->hasTo($optedOutUser->email);
    });
});
