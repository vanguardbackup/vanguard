<?php

use App\Console\Commands\GenerateSSHKeyCommand;

it('should not generate SSH keys if they already exist', function () {
    $privateKeyPath = storage_path('app/ssh/key');
    $publicKeyPath = storage_path('app/ssh/key.pub');

    if (! file_exists(dirname($privateKeyPath))) {
        mkdir(dirname($privateKeyPath), 0700, true);
    }

    file_put_contents($privateKeyPath, 'dummy');
    file_put_contents($publicKeyPath, 'dummy');

    $this->artisan(GenerateSSHKeyCommand::class)
        ->expectsOutputToContain('SSH keys already exist. Cannot generate new keys.')
        ->assertExitCode(0);

    unlink($privateKeyPath);
    unlink($publicKeyPath);
});

it('should not generate SSH keys in a production environment', function () {
    app()->detectEnvironment(fn () => 'production'); // Set the environment to production

    $this->artisan(GenerateSSHKeyCommand::class)
        ->expectsOutputToContain('Cannot generate SSH keys in a production environment.')
        ->assertExitCode(0);
});

it('should generate ssh keys', function () {

    $pathToSSHKeys = storage_path('app/ssh');

    if (file_exists($pathToSSHKeys)) {
        Log::info('Backing up the existing SSH keys to perform the test.');
        rename($pathToSSHKeys, $pathToSSHKeys.'_backup');
    }

    $this->artisan(GenerateSSHKeyCommand::class)
        ->expectsOutputToContain('SSH keys generated successfully.')
        ->assertExitCode(0);

    $this->assertDirectoryExists($pathToSSHKeys);

    $this->assertFileExists($pathToSSHKeys.'/key');
    $this->assertFileExists($pathToSSHKeys.'/key.pub');

    unlink($pathToSSHKeys.'/key');
    unlink($pathToSSHKeys.'/key.pub');

    if (file_exists($pathToSSHKeys.'_backup')) {
        Log::info('Restoring the SSH keys to their original location.');
        rename($pathToSSHKeys.'_backup', $pathToSSHKeys);
    }
});
