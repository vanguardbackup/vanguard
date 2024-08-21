<?php

declare(strict_types=1);

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\User;
use Carbon\Carbon;

it('generates a gravatar URL using the primary email with default size', function (): void {
    $user = User::factory()->create([
        'email' => 'john.doe@example.com',
    ]);

    $expectedUrl = 'https://www.gravatar.com/avatar/' . md5('john.doe@example.com') . '?s=80';

    expect($user->gravatar())->toBe($expectedUrl);
});

it('prioritizes gravatar_email over primary email when generating gravatar URL', function (): void {
    $user = User::factory()->create([
        'email' => 'john.doe@example.com',
        'gravatar_email' => 'johndoe.gravatar@example.com',
    ]);

    $expectedUrl = 'https://www.gravatar.com/avatar/' . md5('johndoe.gravatar@example.com') . '?s=80';

    expect($user->gravatar())->toBe($expectedUrl);
});

it('handles empty or null email addresses gracefully', function (): void {
    $user = User::factory()->create([
        'email' => '',
        'gravatar_email' => null,
    ]);

    $expectedUrl = 'https://www.gravatar.com/avatar/' . md5('') . '?s=80';

    expect($user->gravatar())->toBe($expectedUrl);
});

it('allows custom size for gravatar image', function (): void {
    $user = User::factory()->create([
        'email' => 'john.doe@example.com',
    ]);

    $expectedUrl = 'https://www.gravatar.com/avatar/' . md5('john.doe@example.com') . '?s=200';

    expect($user->gravatar(200))->toBe($expectedUrl);
});

it('uses default size when provided size is zero or negative', function (): void {
    $user = User::factory()->create([
        'email' => 'john.doe@example.com',
    ]);

    $expectedUrl = 'https://www.gravatar.com/avatar/' . md5('john.doe@example.com') . '?s=80';

    expect($user->gravatar(0))->toBe($expectedUrl)
        ->and($user->gravatar(-100))->toBe($expectedUrl);
});

it('truncates size to integer', function (): void {
    $user = User::factory()->create([
        'email' => 'john.doe@example.com',
    ]);

    $expectedUrl = 'https://www.gravatar.com/avatar/' . md5('john.doe@example.com') . '?s=150';

    expect($user->gravatar(150.75))->toBe($expectedUrl);
});

it('returns the first name', function (): void {

    $user = User::factory()->create([
        'name' => 'John Doe',
    ]);

    expect($user->getFirstName())->toBe('John')
        ->and($user->first_name)->toBe('John');
});

it('returns the last name', function (): void {

    $user = User::factory()->create([
        'name' => 'John Doe',
    ]);

    expect($user->getLastName())->toBe('Doe')
        ->and($user->last_name)->toBe('Doe');
});

test('returns true if the user has admin rights', function (): void {

    Config::set('auth.admin_email_addresses', ['admin@email.com']);

    $user = User::factory()->create(['email' => 'admin@email.com']);

    $this->assertContains($user->email, config('auth.admin_email_addresses'));
    $this->assertTrue($user->isAdmin());
});

test('returns false if the user does not have admin rights', function (): void {

    $user = User::factory()->create();

    $this->assertFalse($user->isAdmin());
    $this->assertNotContains($user->email, config('auth.admin_email_addresses'));
});

test('returns the count of backup task logs that are associated with the user', function (): void {

    $user = User::factory()->create();

    $backupTask = BackupTask::factory()->create(['user_id' => $user->id]);
    BackupTaskLog::factory()->create(['backup_task_id' => $backupTask->id, 'finished_at' => now()]);
    BackupTaskLog::factory()->create(['backup_task_id' => $backupTask->id, 'finished_at' => null]);

    $this->assertEquals(1, $user->backupTaskLogCount());
    $this->assertNotEquals(2, $user->backupTaskLogCount());
});

test('returns the count of backup task logs that are associated with the user today', function (): void {

    $user = User::factory()->create();

    $backupTask = BackupTask::factory()->create(['user_id' => $user->id]);
    BackupTaskLog::factory()->create(['backup_task_id' => $backupTask->id, 'created_at' => now()->today()]);

    $this->assertEquals(1, $user->backupTasklogCountToday());
});

test('does not return the count of backup task logs that are associated with the user yesterday', function (): void {

    $user = User::factory()->create();

    $backupTask = BackupTask::factory()->create(['user_id' => $user->id]);
    BackupTaskLog::factory()->create(['backup_task_id' => $backupTask->id, 'created_at' => now()->yesterday()]);

    $this->assertEquals(0, $user->backupTaskLogCountToday());
});

test('returns true if can login with github', function (): void {

    $user = User::factory()->create(['github_id' => 1]);

    $this->assertTrue($user->canLoginWithGithub());
});

test('returns false if can not login with github', function (): void {

    $user = User::factory()->create();

    $this->assertFalse($user->canLoginWithGithub());
});

test('returns only the users that have opted in for backup task summaries', function (): void {
    $userOne = User::factory()->receivesWeeklySummaries()->create();
    $userTwo = User::factory()->doesNotReceiveWeeklySummaries()->create();
    $userThree = User::factory()->doesNotReceiveWeeklySummaries()->create();

    $optedInUsers = User::optedInToReceiveSummaryEmails()->get();

    expect($optedInUsers)->toHaveCount(1)
        ->and($optedInUsers->first()->id)->toBe($userOne->id);
});

test('excludes users who have not opted in for backup task summaries', function (): void {
    $userOne = User::factory()->receivesWeeklySummaries()->create();
    $userTwo = User::factory()->doesNotReceiveWeeklySummaries()->create();
    $userThree = User::factory()->doesNotReceiveWeeklySummaries()->create();

    $optedInUsers = User::optedInToReceiveSummaryEmails()->get();

    expect($optedInUsers)->not->toContain($userTwo)
        ->and($optedInUsers)->not->toContain($userThree);
});

test('returns an empty collection when no users are opted in', function (): void {
    User::factory()->count(3)->doesNotReceiveWeeklySummaries()->create();

    $optedInUsers = User::optedInToReceiveSummaryEmails()->get();

    expect($optedInUsers)->toBeEmpty();
});

test('scope can be chained with other query methods', function (): void {
    $oldUser = User::factory()->create(['weekly_summary_opt_in_at' => now()->subYears(2), 'name' => 'Old Opt-in']);
    $recentUser = User::factory()->create(['weekly_summary_opt_in_at' => now()->subDays(7), 'name' => 'Recent Opt-in']);
    User::factory()->doesNotReceiveWeeklySummaries()->create();

    $recentOptIns = User::optedInToReceiveSummaryEmails()
        ->where('weekly_summary_opt_in_at', '>', now()->subDays(30))
        ->get();

    expect($recentOptIns)->toHaveCount(1)
        ->and($recentOptIns->first()->name)->toBe('Recent Opt-in');
});

test('scope works correctly with pagination', function (): void {
    User::factory()->count(15)->receivesWeeklySummaries()->create();
    User::factory()->count(5)->doesNotReceiveWeeklySummaries()->create();

    $paginatedUsers = User::optedInToReceiveSummaryEmails()->paginate(10);

    expect($paginatedUsers)->toHaveCount(10)
        ->and($paginatedUsers->total())->toBe(15);
});

it('generates correct backup summary data', function (): void {
    $user = User::factory()->create();
    $backupTask = BackupTask::factory()->create(['user_id' => $user->id]);

    $startDate = Carbon::create(2023, 5, 1)->startOfDay(); // A Monday
    $endDate = Carbon::create(2023, 5, 5)->endOfDay();   // A Friday

    BackupTaskLog::factory()->count(3)->create([
        'backup_task_id' => $backupTask->id,
        'created_at' => $startDate->copy()->addDay(),
        'successful_at' => $startDate->copy()->addDay(),
    ]);

    BackupTaskLog::factory()->count(2)->create([
        'backup_task_id' => $backupTask->id,
        'created_at' => $endDate,
        'successful_at' => null,
    ]);

    // Create a log just outside the date range (should not be counted)
    BackupTaskLog::factory()->create([
        'backup_task_id' => $backupTask->id,
        'created_at' => $endDate->copy()->addSecond(),
        'successful_at' => $endDate->copy()->addSecond(),
    ]);

    $summaryData = $user->generateBackupSummaryData([
        'start' => $startDate,
        'end' => $endDate,
    ]);

    expect($summaryData)->toHaveKeys(['total_tasks', 'successful_tasks', 'failed_tasks', 'success_rate', 'date_range'])
        ->and($summaryData['total_tasks'])->toBe(5)
        ->and($summaryData['successful_tasks'])->toBe(3)
        ->and($summaryData['failed_tasks'])->toBe(2)
        ->and($summaryData['success_rate'])->toBe(60.0)
        ->and($summaryData['date_range']['start'])->toBe($startDate->toDateString())
        ->and($summaryData['date_range']['end'])->toBe($endDate->toDateString());
});

it('handles leap years correctly', function (): void {
    $user = User::factory()->create();
    $backupTask = BackupTask::factory()->create(['user_id' => $user->id]);

    $startDate = Carbon::create(2024, 2, 28)->startOfDay(); // Leap year
    $endDate = Carbon::create(2024, 3, 1)->endOfDay();

    BackupTaskLog::factory()->create([
        'backup_task_id' => $backupTask->id,
        'created_at' => Carbon::create(2024, 2, 29, 12, 0, 0),
        'successful_at' => Carbon::create(2024, 2, 29, 12, 0, 0),
    ]);

    $summaryData = $user->generateBackupSummaryData([
        'start' => $startDate,
        'end' => $endDate,
    ]);

    expect($summaryData['total_tasks'])->toBe(1)
        ->and($summaryData['successful_tasks'])->toBe(1);
});

it('returns zero tasks when no backup logs exist in the date range', function (): void {
    $user = User::factory()->create();
    $startDate = Carbon::create(2023, 5, 1)->startOfDay();
    $endDate = Carbon::create(2023, 5, 5)->endOfDay();

    $summaryData = $user->generateBackupSummaryData([
        'start' => $startDate,
        'end' => $endDate,
    ]);

    expect($summaryData['total_tasks'])->toBe(0)
        ->and($summaryData['successful_tasks'])->toBe(0)
        ->and($summaryData['failed_tasks'])->toBe(0)
        ->and($summaryData['success_rate'])->toBe(0);
});

it('generates a mobile api token', function (): void {

    $user = User::factory()->create();

    $user->createMobileToken('Test Token');

    $this->assertDatabaseHas('personal_access_tokens', [
        'name' => 'Test Token',
        'mobile_at' => now(),
    ]);
});

it('returns the correct number of valid backup codes remaining', function (): void {
    Event::fake();
    $user = User::factory()->create();
    $user->createTwoFactorAuth();
    $user->enableTwoFactorAuth();
    $user->generateRecoveryCodes();

    $this->assertEquals(10, $user->backupCodesRemainingCount());
});

it('returns the correct number of used backup codes', function (): void {
    Event::fake();

    $user = User::factory()->create();
    $user->createTwoFactorAuth();
    $user->enableTwoFactorAuth();
    $user->generateRecoveryCodes();

    $this->assertEquals(0, $user->backupCodesUsedCount());
});

it('does not include users without two-factor auth', function (): void {
    User::factory()->create();

    $filteredUsers = User::withOutdatedBackupCodes()->get();

    expect($filteredUsers)->toBeEmpty();
});

it('filters users with outdated backup codes', function (): void {
    Config::set('two-factor.recovery.enabled', true);
    Config::set('two-factor.recovery.codes', 8);
    Config::set('two-factor.recovery.length', 10);

    $userWithOutdatedCodes = User::factory()->create();
    $userWithOutdatedCodes->createTwoFactorAuth();
    $userWithOutdatedCodes->enableTwoFactorAuth();
    $userWithOutdatedCodes->twoFactorAuth->recovery_codes_generated_at = now()->subYear()->subDay();
    $userWithOutdatedCodes->twoFactorAuth->save();

    $userWithRecentCodes = User::factory()->create();
    $userWithRecentCodes->createTwoFactorAuth();
    $userWithRecentCodes->enableTwoFactorAuth();
    $userWithRecentCodes->twoFactorAuth->recovery_codes_generated_at = now()->subMonths(6);
    $userWithRecentCodes->twoFactorAuth->save();

    User::factory()->create();

    $filteredUsers = User::withOutdatedBackupCodes()->get();

    expect($filteredUsers)->toHaveCount(1)
        ->and($filteredUsers->first()->id)->toBe($userWithOutdatedCodes->id);
});

it('considers codes generated exactly one year ago as not outdated', function (): void {
    Config::set('two-factor.recovery.enabled', true);
    Config::set('two-factor.recovery.codes', 8);
    Config::set('two-factor.recovery.length', 10);

    $user = User::factory()->create();
    $user->createTwoFactorAuth();
    $user->enableTwoFactorAuth();
    $user->twoFactorAuth->recovery_codes_generated_at = now()->subYear();
    $user->twoFactorAuth->save();

    $filteredUsers = User::withOutdatedBackupCodes()->get();

    expect($filteredUsers)->toBeEmpty();
});

it('returns true if quiet mode is enabled', function (): void {
    $user = User::factory()->quietMode()->create();

    $this->assertTrue($user->hasQuietMode());
});

it('returns false if quiet mode is disabled', function (): void {
    $user = User::factory()->create();

    $this->assertFalse($user->hasQuietMode());
});

it('scopes query to users with quiet mode enabled', function (): void {
    User::factory()->count(3)->create();
    User::factory()->count(2)->quietMode()->create();

    $quietUsers = User::withQuietMode()->get();

    expect($quietUsers)->toHaveCount(2)
        ->each(function ($user): void {
            expect($user->quiet_until)->not->toBeNull();
        });
});

it('does not include users without quiet mode', function (): void {
    User::factory()->count(3)->create(['quiet_until' => null]);
    User::factory()->count(2)->quietMode()->create();

    $nonQuietUsers = User::query()->whereNotIn('id', User::withQuietMode()->pluck('id'))->get();

    expect($nonQuietUsers)->toHaveCount(3);

    $nonQuietUsers->each(function ($user): void {
        expect($user->quiet_until)->toBeNull();
    });
});

it('resets a users quiet mode', function (): void {
    $user = User::factory()->quietMode()->create();

    $user->clearQuietMode();

    $this->assertFalse($user->hasQuietMode());
});

it('does not reset quiet mode if quiet mode not set', function (): void {
    $user = User::factory()->create();

    $this->assertFalse($user->hasQuietMode());

    $user->clearQuietMode();

    $this->assertFalse($user->hasQuietMode());
});
