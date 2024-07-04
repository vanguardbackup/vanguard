<?php

declare(strict_types=1);

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\User;

it('can generate a gravatar default image', function () {
    $user = User::factory()->create([
        'email' => 'john.doe@email.com',
    ]);

    expect($user->gravatar())->toBe('https://www.gravatar.com/avatar/8f6e96274c2abf617a3987e74e9e757e');
});

it('returns the first name', function () {

    $user = User::factory()->create([
        'name' => 'John Doe',
    ]);

    expect($user->getFirstName())->toBe('John')
        ->and($user->first_name)->toBe('John');
});

it('returns the last name', function () {

    $user = User::factory()->create([
        'name' => 'John Doe',
    ]);

    expect($user->getLastName())->toBe('Doe')
        ->and($user->last_name)->toBe('Doe');
});

test('returns true if the user has admin rights', function () {

    Config::set('auth.admin_email_addresses', ['admin@email.com']);

    $user = User::factory()->create(['email' => 'admin@email.com']);

    $this->assertContains($user->email, config('auth.admin_email_addresses'));
    $this->assertTrue($user->isAdmin());
});

test('returns false if the user does not have admin rights', function () {

    $user = User::factory()->create();

    $this->assertFalse($user->isAdmin());
    $this->assertNotContains($user->email, config('auth.admin_email_addresses'));
});

test('returns the count of backup task logs that are associated with the user', function () {

    $user = User::factory()->create();

    $backupTask = BackupTask::factory()->create(['user_id' => $user->id]);
    BackupTaskLog::factory()->create(['backup_task_id' => $backupTask->id, 'finished_at' => now()]);
    BackupTaskLog::factory()->create(['backup_task_id' => $backupTask->id, 'finished_at' => null]);

    $this->assertEquals(1, $user->backupTaskLogCount());
    $this->assertNotEquals(2, $user->backupTaskLogCount());
});

test('returns the count of backup task logs that are associated with the user today', function () {

    $user = User::factory()->create();

    $backupTask = BackupTask::factory()->create(['user_id' => $user->id]);
    BackupTaskLog::factory()->create(['backup_task_id' => $backupTask->id, 'created_at' => now()->today()]);

    $this->assertEquals(1, $user->backupTasklogCountToday());
});

test('does not return the count of backup task logs that are associated with the user yesterday', function () {

    $user = User::factory()->create();

    $backupTask = BackupTask::factory()->create(['user_id' => $user->id]);
    BackupTaskLog::factory()->create(['backup_task_id' => $backupTask->id, 'created_at' => now()->yesterday()]);

    $this->assertEquals(0, $user->backupTaskLogCountToday());
});

test('returns true if can login with github', function () {

    $user = User::factory()->create(['github_id' => 1]);

    $this->assertTrue($user->canLoginWithGithub());
});

test('returns false if can not login with github', function () {

    $user = User::factory()->create();

    $this->assertFalse($user->canLoginWithGithub());
});
