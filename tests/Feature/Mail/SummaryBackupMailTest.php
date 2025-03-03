<?php

declare(strict_types=1);

use App\Mail\User\SummaryBackupMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Mail::fake();
    Config::set('app.name', 'Vanguard');
    Route::get('/backup-tasks', fn (): string => '')->name('backup-tasks.index');
    Route::get('/overview', fn (): string => '')->name('overview');
});

test('email content is correct for partially successful backups', function (): void {
    $user = User::factory()->create(['name' => 'John Doe']);
    $startDate = Carbon::parse('2023-07-10');
    $endDate = Carbon::parse('2023-07-16');

    $data = [
        'total_tasks' => 10,
        'successful_tasks' => 8,
        'failed_tasks' => 2,
        'success_rate' => 80.0,
        'date_range' => [
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString(),
        ],
    ];

    $mailable = new SummaryBackupMail($data, $user);

    $mailable->assertHasSubject('Your Backup Performance Recap for Jul 10 - Jul 16, 2023');

    $content = $mailable->render();

    expect($content)
        ->toContain('Hey John,')
        ->toContain("Here's a summary of your backup activities from 2023-07-10 to 2023-07-16")
        ->toContain('Total Backups: 10')
        ->toContain('Successful: 8')
        ->toContain('Failed: 2')
        ->toContain('Success Rate: 80.0%')
        ->toContain("Good, but there's room for improvement. Check your failed backups logs.")
        ->toContain('Action Required')
        ->toContain('Some of your backup tasks failed this week.')
        ->toContain('Review Backup Tasks');
});

test('email content is correct for 100% successful backups', function (): void {
    $user = User::factory()->create(['name' => 'Jane Doe']);
    $startDate = Carbon::parse('2023-07-10');
    $endDate = Carbon::parse('2023-07-16');

    $data = [
        'total_tasks' => 10,
        'successful_tasks' => 10,
        'failed_tasks' => 0,
        'success_rate' => 100.0,
        'date_range' => [
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString(),
        ],
    ];

    $mailable = new SummaryBackupMail($data, $user);

    $content = $mailable->render();

    expect($content)
        ->toContain('Hey Jane,')
        ->toContain('Success Rate: 100.0%')
        ->toContain('👍 Great job! Most of your backup tasks were successful.')
        ->toContain('Keep Up the Good Work!')
        ->toContain('All your backups were successful this week.')
        ->toContain('View Overview')
        ->not->toContain('Action Required');
});

test('email displays correct content for 90% successful backups', function (): void {
    $user = User::factory()->create(['name' => 'Alice']);
    $startDate = Carbon::parse('2023-07-10');
    $endDate = Carbon::parse('2023-07-16');

    $data = [
        'total_tasks' => 10,
        'successful_tasks' => 9,
        'failed_tasks' => 1,
        'success_rate' => 90.0,
        'date_range' => [
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString(),
        ],
    ];

    $mailable = new SummaryBackupMail($data, $user);
    $content = $mailable->render();

    expect($content)
        ->toContain('Success Rate: 90.0%')
        ->toContain('👍 Great job! Most of your backup tasks were successful.')
        ->toContain('Action Required')
        ->toContain('Some of your backup tasks failed this week.')
        ->toContain('Review Backup Tasks');
});

test('email contains correct app name', function (): void {
    $user = User::factory()->create(['name' => 'Bob']);
    $data = [
        'total_tasks' => 10,
        'successful_tasks' => 10,
        'failed_tasks' => 0,
        'success_rate' => 100.0,
        'date_range' => ['start' => '2023-07-10', 'end' => '2023-07-16'],
    ];

    $mailable = new SummaryBackupMail($data, $user);
    $content = $mailable->render();

    expect($content)
        ->toContain('Thank you for using Vanguard to keep your data safe and secure.')
        ->toContain('Thanks,');
});

test('mailable uses queue', function (): void {
    $user = User::factory()->create();
    $data = [
        'total_tasks' => 10,
        'successful_tasks' => 10,
        'failed_tasks' => 0,
        'success_rate' => 100.0,
        'date_range' => ['start' => '2023-07-10', 'end' => '2023-07-16'],
    ];

    $mailable = new SummaryBackupMail($data, $user);

    expect($mailable)->toBeInstanceOf(ShouldQueue::class);
});
