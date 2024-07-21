<?php

declare(strict_types=1);

namespace Tests\Testing\Fakes;

use App\Enums\ConnectionType;
use App\Exceptions\ServerConnectionException;
use App\Facades\ServerConnection;
use App\Models\RemoteServer;
use App\Testing\ServerConnectionFake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerConnectionFakeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ServerConnection::fake();
    }

    /**
     * @test
     */
    public function fake_connection_success(): void
    {
        $this->getFake()->connect();

        ServerConnection::assertConnected();
    }

    /**
     * @test
     */
    public function fake_connection_failure(): void
    {
        $this->getFake()->shouldConnect(false, 'Connection timed out');

        $this->expectException(ServerConnectionException::class);
        $this->expectExceptionMessage('Connection timed out');

        $this->getFake()->connect();
    }

    /**
     * @test
     */
    public function fake_connection_to_specific_server(): void
    {
        $server = RemoteServer::factory()->create(['ip_address' => '192.168.1.1']);

        $serverConnectionFake = $this->getFake();
        $serverConnectionFake->setConnectedServer($server);
        $serverConnectionFake->connect();

        ServerConnection::assertConnectedTo(function ($connectedServer) use ($server): bool {
            return $connectedServer->id === $server->id &&
                $connectedServer->ip_address === '192.168.1.1';
        });
    }

    /**
     * @test
     */
    public function fake_command_execution(): void
    {
        $serverConnectionFake = $this->getFake();
        $serverConnectionFake->withCommandResponse('ls -la', 'total 0\ndrwxr-xr-x  2 user user  40 Jul 21 12:34 .');

        $serverConnectionFake->connect();
        $output = $serverConnectionFake->executeCommand('ls -la');

        $this->assertEquals('total 0\ndrwxr-xr-x  2 user user  40 Jul 21 12:34 .', $output);
        ServerConnection::assertCommandExecuted('ls -la');
    }

    /**
     * @test
     */
    public function fake_file_upload(): void
    {
        $serverConnectionFake = $this->getFake();
        $serverConnectionFake->connect();
        $serverConnectionFake->uploadFile('/local/path/file.txt', '/remote/path/file.txt');

        ServerConnection::assertFileUploaded('/local/path/file.txt', '/remote/path/file.txt');
    }

    /**
     * @test
     */
    public function fake_file_download(): void
    {
        $serverConnectionFake = $this->getFake();
        $serverConnectionFake->connect();
        $serverConnectionFake->downloadFile('/remote/path/file.txt', '/local/path/file.txt');

        ServerConnection::assertFileDownloaded('/remote/path/file.txt', '/local/path/file.txt');
    }

    /**
     * @test
     */
    public function fake_disconnect(): void
    {
        $serverConnectionFake = $this->getFake();
        $serverConnectionFake->connect();
        ServerConnection::assertConnected();

        $serverConnectionFake->disconnect();
        ServerConnection::assertNotConnected();
    }

    /**
     * @test
     */
    public function fake_set_private_key_path(): void
    {
        $serverConnectionFake = $this->getFake()->setPrivateKeyPath('/path/to/private/key');

        $this->assertInstanceOf(ServerConnectionFake::class, $serverConnectionFake);
    }

    /**
     * @test
     */
    public function fake_get_connection_type(): void
    {
        $connectionType = $this->getFake()->getConnectionType();

        $this->assertEquals(ConnectionType::SSH, $connectionType);
    }

    /**
     * @test
     */
    public function fake_multiple_command_responses(): void
    {
        $serverConnectionFake = $this->getFake();
        $serverConnectionFake->withCommandResponse('ls', 'file1.txt file2.txt')
            ->withCommandResponse('pwd', '/home/user');

        $serverConnectionFake->connect();

        $lsOutput = $serverConnectionFake->executeCommand('ls');
        $pwdOutput = $serverConnectionFake->executeCommand('pwd');

        $this->assertEquals('file1.txt file2.txt', $lsOutput);
        $this->assertEquals('/home/user', $pwdOutput);

        ServerConnection::assertCommandExecuted('ls');
        ServerConnection::assertCommandExecuted('pwd');
    }

    /**
     * @test
     */
    public function fake_multiple_file_operations(): void
    {
        $serverConnectionFake = $this->getFake();
        $serverConnectionFake->connect();
        $serverConnectionFake->uploadFile('/local/file1.txt', '/remote/file1.txt');
        $serverConnectionFake->uploadFile('/local/file2.txt', '/remote/file2.txt');
        $serverConnectionFake->downloadFile('/remote/file3.txt', '/local/file3.txt');

        ServerConnection::assertFileUploaded('/local/file1.txt', '/remote/file1.txt');
        ServerConnection::assertFileUploaded('/local/file2.txt', '/remote/file2.txt');
        ServerConnection::assertFileDownloaded('/remote/file3.txt', '/local/file3.txt');
    }

    private function getFake(): ServerConnectionFake
    {
        return app(ServerConnectionFake::class);
    }
}
