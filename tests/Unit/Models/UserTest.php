<?php

declare(strict_types=1);

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\User;

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
