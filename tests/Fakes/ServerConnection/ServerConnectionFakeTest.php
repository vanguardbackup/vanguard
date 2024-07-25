<?php

declare(strict_types=1);

use App\Models\RemoteServer;
use App\Support\ServerConnection\Connection;
use App\Support\ServerConnection\Exceptions\ConnectionException;
use App\Support\ServerConnection\PendingConnection;
use App\Support\ServerConnection\ServerConnectionManager;

beforeEach(function (): void {
    ServerConnectionManager::fake();
});

afterEach(function (): void {
    ServerConnectionManager::reset();
});

it('properly sets fake mode', function (): void {
    expect(ServerConnectionManager::isFake())->toBeTrue();
});

it('returns fake private key content', function (): void {
    $privateKey = ServerConnectionManager::getDefaultPrivateKey();
    expect($privateKey)->toBe('fake_private_key_content');
});

it('returns fake public key content', function (): void {
    $publicKey = ServerConnectionManager::getDefaultPublicKey();
    expect($publicKey)->toBe('fake_public_key_content');
});

it('returns fake passphrase', function (): void {
    $passphrase = ServerConnectionManager::getDefaultPassphrase();
    expect($passphrase)->toBe('fake_passphrase');
});

it('returns fake private key path', function (): void {
    $path = ServerConnectionManager::getDefaultPrivateKeyPath();
    expect($path)->toBe('fake/path/to/private/key');
});

it('creates a PendingConnection when connecting', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    expect($pendingConnection)->toBeInstanceOf(PendingConnection::class);
});

it('records connection attempts', function (): void {
    ServerConnectionManager::connect('example.com', 2222, 'user');
    ServerConnectionManager::assertConnectionAttempted([
        'host' => 'example.com',
        'port' => 2222,
        'username' => 'user',
    ]);
});

it('creates a PendingConnection when connecting from a model', function (): void {
    $remoteServer = RemoteServer::factory()->create();

    $pendingConnection = ServerConnectionManager::connectFromModel($remoteServer);
    expect($pendingConnection)->toBeInstanceOf(PendingConnection::class);
});

it('simulates establishing a connection', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();

    expect($connection)->toBeInstanceOf(Connection::class);
    ServerConnectionManager::assertConnected();
});

it('simulates running a command', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();

    $connection->run('ls -la');
    ServerConnectionManager::assertCommandRan('ls -la');
});

it('simulates file upload', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();

    $connection->upload('/local/path', '/remote/path');
    ServerConnectionManager::assertFileUploaded('/local/path', '/remote/path');
});

it('simulates file download', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();

    $connection->download('/remote/path', '/local/path');
    ServerConnectionManager::assertFileDownloaded('/remote/path', '/local/path');
});

it('simulates disconnecting', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();

    $connection->disconnect();
    ServerConnectionManager::assertDisconnected();
});

it('allows setting custom output for commands', function (): void {
    ServerConnectionManager::fake(function ($fake): void {
        $fake->setOutput('Custom output');
    });

    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();

    $output = $connection->run('some command');
    expect($output)->toBe('Custom output');
});

it('throws an exception when asserting on non-fake connection', function (): void {
    ServerConnectionManager::reset();
    ServerConnectionManager::assertConnected();
})->throws(RuntimeException::class, 'Server connection is not in fake mode.');

it('simulates connection failure', function (): void {
    ServerConnectionManager::fake(function ($fake): void {
        $fake->shouldNotConnect();
    });

    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');

    expect(fn (): Connection => $pendingConnection->establish())->toThrow(ConnectionException::class);
    ServerConnectionManager::assertNotConnected();
});

it('simulates multiple command runs', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();

    $connection->run('command1');
    $connection->run('command2');
    $connection->run('command3');

    ServerConnectionManager::assertCommandRan('command1');
    ServerConnectionManager::assertCommandRan('command2');
    ServerConnectionManager::assertCommandRan('command3');
    ServerConnectionManager::assertAnyCommandRan();
});

it('asserts no commands were run', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $pendingConnection->establish();

    ServerConnectionManager::assertNoCommandsRan();
});

it('simulates multiple file uploads', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();

    $connection->upload('/local/path1', '/remote/path1');
    $connection->upload('/local/path2', '/remote/path2');

    ServerConnectionManager::assertFileUploaded('/local/path1', '/remote/path1');
    ServerConnectionManager::assertFileUploaded('/local/path2', '/remote/path2');
});

it('simulates multiple file downloads', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();

    $connection->download('/remote/path1', '/local/path1');
    $connection->download('/remote/path2', '/local/path2');

    ServerConnectionManager::assertFileDownloaded('/remote/path1', '/local/path1');
    ServerConnectionManager::assertFileDownloaded('/remote/path2', '/local/path2');
});

it('resets fake state correctly', function (): void {
    ServerConnectionManager::connect('example.com', 2222, 'user');
    ServerConnectionManager::reset();

    expect(ServerConnectionManager::isFake())->toBeFalse();
});

it('allows custom setup of fake instance', function (): void {
    ServerConnectionManager::fake(function ($fake): void {
        $fake->shouldConnect()->setOutput('Custom output');
    });

    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();

    expect($connection->run('command'))->toBe('Custom output');
});

it('simulates connection from model with custom attributes', function (): void {
    $remoteServer = RemoteServer::factory()->create([
        'ip_address' => '127.0.0.1',
        'port' => 2222,
        'username' => 'customuser',
    ]);

    ServerConnectionManager::connectFromModel($remoteServer);
    ServerConnectionManager::assertConnectionAttempted([
        'host' => '127.0.0.1',
        'port' => 2222,
        'username' => 'customuser',
    ]);
});

it('throws exception when trying to run command on closed connection', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();
    $connection->disconnect();

    expect(fn (): string => $connection->run('command'))->toThrow(RuntimeException::class, 'Cannot perform operation: Connection is closed.');
});

it('throws exception when trying to upload file on closed connection', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();
    $connection->disconnect();

    expect(fn (): bool => $connection->upload('/local/path', '/remote/path'))->toThrow(RuntimeException::class, 'Cannot perform operation: Connection is closed.');
});

it('throws exception when trying to download file on closed connection', function (): void {
    $pendingConnection = ServerConnectionManager::connect('example.com', 2222, 'user');
    $connection = $pendingConnection->establish();
    $connection->disconnect();

    expect(fn (): bool => $connection->download('/remote/path', '/local/path'))->toThrow(RuntimeException::class, 'Cannot perform operation: Connection is closed.');
});
