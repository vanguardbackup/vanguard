<?php

use App\Console\Commands\EncryptDatabasePasswordsCommand;
use App\Models\RemoteServer;

it('exits if there are no remote servers', function () {

    $this->artisan(EncryptDatabasePasswordsCommand::class)
        ->expectsOutputToContain('No remote servers found.')
        ->assertExitCode(0);
});

it('skips encryption if the database password is empty', function () {

    $remoteServer = RemoteServer::factory()->create([
        'database_password' => '',
    ]);

    $this->artisan(EncryptDatabasePasswordsCommand::class)
        ->expectsOutputToContain("Database password for remote server {$remoteServer->label} is empty. Skipping encryption.")
        ->assertExitCode(0);
});

it('encrypts the database password', function () {

    $remoteServer = RemoteServer::factory()->create([
        'database_password' => 'password',
    ]);

    $this->artisan(EncryptDatabasePasswordsCommand::class)
        ->expectsOutputToContain('1 database passwords have been encrypted.')
        ->assertExitCode(0);

    $this->assertNotSame('password', $remoteServer->fresh()->database_password);
    $this->assertSame('password', Crypt::decryptString($remoteServer->fresh()->database_password));
});

it('does not encrypt the database password if it is already encrypted', function () {

    $remoteServer = RemoteServer::factory()->create([
        'database_password' => Crypt::encryptString('password'),
    ]);

    $this->artisan(EncryptDatabasePasswordsCommand::class)
        ->expectsOutputToContain('0 database passwords have been encrypted.')
        ->assertExitCode(0);

    $this->assertSame('password', Crypt::decryptString($remoteServer->fresh()->database_password));
});
