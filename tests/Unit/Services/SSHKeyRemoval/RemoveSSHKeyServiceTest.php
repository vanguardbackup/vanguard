<?php

declare(strict_types=1);

use App\Models\RemoteServer;
use App\Services\RemoveSSHKey\Contracts\KeyRemovalNotifierInterface;
use App\Services\RemoveSSHKey\Contracts\SSHClientInterface;
use App\Services\RemoveSSHKey\Contracts\SSHKeyProviderInterface;
use App\Services\RemoveSSHKey\RemoveSSHKeyService;

beforeEach(function (): void {
    $this->sshClient = Mockery::mock(SSHClientInterface::class);
    $this->notifier = Mockery::mock(KeyRemovalNotifierInterface::class);
    $this->sshKeyProvider = Mockery::mock(SSHKeyProviderInterface::class);

    $this->service = new RemoveSSHKeyService(
        $this->sshClient,
        $this->notifier,
        $this->sshKeyProvider
    );

    $this->remoteServer = new RemoteServer([
        'id' => 1,
        'ip_address' => '192.168.1.1',
        'port' => 22,
        'username' => 'testuser',
    ]);
});

it('successfully removes SSH key from remote server', function (): void {
    $this->sshKeyProvider->shouldReceive('getPrivateKey')->once()->andReturn('private-key');
    $this->sshKeyProvider->shouldReceive('getPublicKey')->once()->andReturn('public-key');

    $this->sshClient->shouldReceive('connect')
        ->with('192.168.1.1', 22, 'testuser', 'private-key')
        ->once()
        ->andReturn(true);

    $this->sshClient->shouldReceive('executeCommand')
        ->with(Mockery::pattern('/sed -i -e/'))
        ->once();

    $this->notifier->shouldReceive('notifySuccess')
        ->with($this->remoteServer)
        ->once();

    $this->service->handle($this->remoteServer);
});

it('handles connection failure gracefully', function (): void {
    $this->sshKeyProvider->shouldReceive('getPrivateKey')->once()->andReturn('private-key');

    $this->sshClient->shouldReceive('connect')
        ->with('192.168.1.1', 22, 'testuser', 'private-key')
        ->once()
        ->andReturn(false);

    $this->notifier->shouldReceive('notifyFailure')
        ->with($this->remoteServer, Mockery::type('string'))
        ->once();

    $this->service->handle($this->remoteServer);
});

it('logs appropriate messages during the process', function (): void {
    $this->sshKeyProvider->shouldReceive('getPrivateKey')->once()->andReturn('private-key');
    $this->sshKeyProvider->shouldReceive('getPublicKey')->once()->andReturn('public-key');

    $this->sshClient->shouldReceive('connect')->once()->andReturn(true);
    $this->sshClient->shouldReceive('executeCommand')->once();

    $this->notifier->shouldReceive('notifySuccess')->once();

    Log::shouldReceive('info')->times(3)
        ->withArgs(function ($message): bool {
            return in_array($message, [
                'Initiating SSH key removal process.',
                'SSH key removed from server.',
                'User notified of successful key removal.',
            ]);
        });

    $this->service->handle($this->remoteServer);
});

afterEach(function (): void {
    Mockery::close();
});
