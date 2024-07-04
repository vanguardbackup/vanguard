<?php

declare(strict_types=1);

use App\Jobs\CheckRemoteServerConnectionJob;
use App\Jobs\RemoteServers\RemoveServerJob;
use App\Jobs\RemoteServers\RemoveSSHKeyJob;
use App\Models\RemoteServer;

it('sets the last connected at timestamp', function (): void {

    $server = RemoteServer::factory()->create();

    $this->assertNull($server->last_connected_at);

    $server->updateLastConnectedAt();

    $this->assertNotNull($server->last_connected_at);
    $this->assertGreaterThan(now()->subMinute(), $server->last_connected_at);
});

it('checks if the server has a database password', function (): void {

    $server = RemoteServer::factory()->create(['database_password' => 'secret']);

    expect($server->hasDatabasePassword())->toBeTrue();
});

it('checks if the server does not have a database password', function (): void {

    $server = RemoteServer::factory()->create(['database_password' => null]);

    expect($server->hasDatabasePassword())->toBeFalse();
});

it('marks the server as checking', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_CHECKING]);

    $server->markAsChecking();

    expect($server->connectivity_status)->toBe(RemoteServer::STATUS_CHECKING);
});

it('marks the server as online', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_CHECKING]);

    $server->markAsOnline();

    expect($server->connectivity_status)->toBe(RemoteServer::STATUS_ONLINE);
});

it('runs the server connection check', function (): void {
    Queue::fake();

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_UNKNOWN]);

    $server->runServerConnectionCheck();

    Queue::assertPushed(CheckRemoteServerConnectionJob::class, function ($job) use ($server): bool {
        return $job->remoteServerId === $server->id;
    });

    expect($server->connectivity_status)->toBe(RemoteServer::STATUS_CHECKING);
});

it('marks the server as online if the status is not online', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_UNKNOWN]);

    $server->markAsOnlineIfStatusIsNotOnline();

    expect($server->connectivity_status)->toBe(RemoteServer::STATUS_ONLINE);
});

it('does not mark the server as online if the status is already online', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_ONLINE]);

    $server->markAsOnlineIfStatusIsNotOnline();

    expect($server->connectivity_status)->toBe(RemoteServer::STATUS_ONLINE);
});

it('returns true for isOnline if the server status is online', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_ONLINE]);

    expect($server->isOnline())->toBeTrue();
});

it('returns false for isOnline if the server status is offline', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_OFFLINE]);

    expect($server->isOnline())->toBeFalse();
});

it('returns false for isOnline if the server status is checking', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_CHECKING]);

    expect($server->isOnline())->toBeFalse();
});

it('returns false for isOnline if the server status is unknown', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_UNKNOWN]);

    expect($server->isOnline())->toBeFalse();
});

it('returns true for isOffline if the server status is offline', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_OFFLINE]);

    expect($server->isOffline())->toBeTrue();
});

it('returns false for isOffline if the server status is online', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_ONLINE]);

    expect($server->isOffline())->toBeFalse();
});

it('returns false for isOffline if the server status is checking', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_CHECKING]);

    expect($server->isOffline())->toBeFalse();
});

it('returns false for isOffline if the server status is unknown', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_UNKNOWN]);

    expect($server->isOffline())->toBeFalse();
});

it('returns true for isChecking if the server status is checking', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_CHECKING]);

    expect($server->isChecking())->toBeTrue();
});

it('returns false for isChecking if the server status is online', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_ONLINE]);

    expect($server->isChecking())->toBeFalse();
});

it('returns false for isChecking if the server status is offline', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_OFFLINE]);

    expect($server->isChecking())->toBeFalse();
});

it('returns false for isChecking if the server status is unknown', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_UNKNOWN]);

    expect($server->isChecking())->toBeFalse();
});

it('returns true for isUnknown if the server status is unknown', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_UNKNOWN]);

    expect($server->isUnknown())->toBeTrue();
});

it('returns false for isUnknown if the server status is online', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_ONLINE]);

    expect($server->isUnknown())->toBeFalse();
});

it('returns false for isUnknown if the server status is offline', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_OFFLINE]);

    expect($server->isUnknown())->toBeFalse();
});

it('returns false for isUnknown if the server status is checking', function (): void {

    $server = RemoteServer::factory()->create(['connectivity_status' => RemoteServer::STATUS_CHECKING]);

    expect($server->isUnknown())->toBeFalse();
});

it('gets the decrypted database password', function (): void {
    $password = 'secret'; // Plain text password
    $hashedPassword = Crypt::encryptString($password);

    $server = RemoteServer::factory()->create(['database_password' => $hashedPassword]);

    expect($server->getDecryptedDatabasePassword())->toBe($password);
});

it('returns null for getDecryptedDatabasePassword if the server does not have a database password', function (): void {

    $server = RemoteServer::factory()->create(['database_password' => null]);

    expect($server->getDecryptedDatabasePassword())->toBeNull();
});

it('returns null for getDecryptedDatabasePassword if the server has an empty database password', function (): void {

    $server = RemoteServer::factory()->create(['database_password' => '']);

    expect($server->getDecryptedDatabasePassword())->toBeNull();
});

it('returns true for isDatabasePasswordEncrypted if the server has an encrypted database password', function (): void {

    $server = RemoteServer::factory()->create(['database_password' => Crypt::encryptString('secret')]);

    expect($server->isDatabasePasswordEncrypted())->toBeTrue();
});

it('returns false for isDatabasePasswordEncrypted if the server does not have a database password', function (): void {

    $server = RemoteServer::factory()->create(['database_password' => null]);

    expect($server->isDatabasePasswordEncrypted())->toBeFalse();
});

it('returns false for isDatabasePasswordEncrypted if the server has an empty database password', function (): void {

    $server = RemoteServer::factory()->create(['database_password' => '']);

    expect($server->isDatabasePasswordEncrypted())->toBeFalse();
});

it('returns false for isDatabasePasswordEncrypted if the server has a plain text database password', function (): void {

    $server = RemoteServer::factory()->create(['database_password' => 'secret']);

    expect($server->isDatabasePasswordEncrypted())->toBeFalse();
});

it('dispatches a job to remove the ssh key', function (): void {

    Queue::fake();

    $server = RemoteServer::factory()->create();

    $server->removeSSHKey();

    Queue::assertPushed(RemoveSSHKeyJob::class, function ($job) use ($server): bool {
        return $job->remoteServer->id === $server->id;
    });
});

it('dispatches a job to delete the remote server', function (): void {
    Queue::fake();

    $server = RemoteServer::factory()->create();

    $server->removeServer();

    $this->assertTrue($server->isMarkedForDeletion());

    Queue::assertPushed(RemoveServerJob::class, function ($job) use ($server): bool {
        return $job->remoteServer->id === $server->id;
    });

    Queue::assertPushed(RemoveSSHKeyJob::class, function ($job) use ($server): bool {
        return $job->remoteServer->id === $server->id;
    });
});

it('updates the marked for deletion column', function (): void {

    $server = RemoteServer::factory()->create();

    $server->setMarkedForDeletion();

    $this->assertNotNull($server->marked_for_deletion_at);
});

it('returns true if marked for deletion', function (): void {

    $server = RemoteServer::factory()->create(['marked_for_deletion_at' => now()]);

    $this->assertTrue($server->isMarkedForDeletion());
});

it('returns false if not marked for deletion', function (): void {

    $server = RemoteServer::factory()->create(['marked_for_deletion_at' => null]);

    $this->assertFalse($server->isMarkedForDeletion());
});
