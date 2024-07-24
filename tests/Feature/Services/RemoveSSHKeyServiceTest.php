<?php

declare(strict_types=1);

use App\Facades\ServerConnection;
use App\Mail\RemoteServers\FailedToRemoveKey;
use App\Mail\RemoteServers\SuccessfullyRemovedKey;
use App\Models\RemoteServer;
use App\Services\RemoveSSHKey\RemoveSSHKeyService;

it('successfully removes SSH keys and sends success email', function (): void {
    Mail::fake();
    ServerConnection::fake();
    $remoteServer = RemoteServer::factory()->create();
    $service = new RemoveSSHKeyService;

    $service->handle($remoteServer);

    ServerConnection::assertConnected();
    ServerConnection::assertAnyCommandRan();

    Mail::assertQueued(SuccessfullyRemovedKey::class, function ($mail) use ($remoteServer): bool {
        return $mail->remoteServer->id === $remoteServer->id;
    });

    Mail::assertNotQueued(FailedToRemoveKey::class);
});

it('handles connection failure by not running commands and sending failure email', function (): void {
    Mail::fake();
    ServerConnection::fake()->shouldNotConnect();
    $remoteServer = RemoteServer::factory()->create();
    $service = new RemoveSSHKeyService;

    $service->handle($remoteServer);

    ServerConnection::assertNotConnected();
    ServerConnection::assertNoCommandsRan();

    Mail::assertQueued(FailedToRemoveKey::class, function ($mail) use ($remoteServer): bool {
        return $mail->remoteServer->id === $remoteServer->id;
    });

    Mail::assertNotQueued(SuccessfullyRemovedKey::class);
});
