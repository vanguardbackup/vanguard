<?php

declare(strict_types=1);

use App\Console\Commands\GenerateSSHKeyCommand;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\RemoteServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class, RefreshDatabase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function test_create_keys(): void
{
    $pathToSSHKeys = storage_path('app/ssh');
    $backupPath = $pathToSSHKeys . '_backup';

    // If the SSH directory exists, back it up
    if (File::isDirectory($pathToSSHKeys)) {
        Log::info('Backing up the existing SSH keys to perform the test.');

        // If a backup already exists, delete it
        if (File::isDirectory($backupPath)) {
            File::deleteDirectory($backupPath);
        }

        // Move the current SSH directory to the backup location
        File::moveDirectory($pathToSSHKeys, $backupPath);
    }

    // Ensure the SSH directory exists
    File::makeDirectory($pathToSSHKeys, 0755, true, true);

    Artisan::call(GenerateSSHKeyCommand::class);
}

function test_restore_keys(): void
{
    $pathToSSHKeys = storage_path('app/ssh');
    $backupPath = $pathToSSHKeys . '_backup';

    // Delete the current SSH directory
    if (File::isDirectory($pathToSSHKeys)) {
        File::deleteDirectory($pathToSSHKeys);
    }

    // If a backup exists, restore it
    if (File::isDirectory($backupPath)) {
        Log::info('Restoring the SSH keys to their original location.');
        File::moveDirectory($backupPath, $pathToSSHKeys);
    }
}

function createUserWithBackupTaskAndDependencies(): array
{
    $user = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create(['user_id' => $user->id]);
    $backupTask = BackupTask::factory()->create([
        'user_id' => $user->id,
        'remote_server_id' => $remoteServer->id,
        'maximum_backups_to_keep' => 5,
        'source_path' => null,
    ]);
    $backupDestination = BackupDestination::factory()->create(['user_id' => $user->id]);

    return [
        'user' => $user,
        'remoteServer' => $remoteServer,
        'backupTask' => $backupTask,
        'backupDestination' => $backupDestination,
    ];
}

