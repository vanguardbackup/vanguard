<?php

declare(strict_types=1);

namespace Tests\Fakes\ServerConnection;

use App\Models\RemoteServer;
use App\Support\ServerConnection\Connection;
use App\Support\ServerConnection\Exceptions\ConnectionException;
use App\Support\ServerConnection\Fakes\ServerConnectionFake;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ServerConnectionFakeTest extends TestCase
{
    private ServerConnectionFake $serverConnectionFake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverConnectionFake = new ServerConnectionFake;
    }

    /** @test */
    public function it_can_connect_from_model(): void
    {
        $remoteServer = new RemoteServer;
        $remoteServer->getAttribute('ip_address') = 'example.com';
        $remoteServer->getAttribute('port') = 2222;
        $remoteServer->getAttribute('username') = 'testuser';

        $serverConnectionFake = $this->serverConnectionFake->connectFromModel($remoteServer);

        $this->assertInstanceOf(ServerConnectionFake::class, $serverConnectionFake);
        $this->serverConnectionFake->assertConnectionAttempted([
            'host' => 'example.com',
            'port' => 2222,
            'username' => 'testuser',
        ]);
    }

    /** @test */
    public function it_can_connect_with_custom_details(): void
    {
        $serverConnectionFake = $this->serverConnectionFake->connect('custom.com', 2222, 'customuser');

        $this->assertInstanceOf(ServerConnectionFake::class, $serverConnectionFake);
        $this->serverConnectionFake->assertConnectionAttempted([
            'host' => 'custom.com',
            'port' => 2222,
            'username' => 'customuser',
        ]);
    }

    /** @test */
    public function it_can_establish_connection(): void
    {
        $connection = $this->serverConnectionFake->establish();

        $this->assertInstanceOf(Connection::class, $connection);
        $this->serverConnectionFake->assertConnected();
    }

    /** @test */
    public function it_can_fail_to_connect(): void
    {
        $this->serverConnectionFake->shouldConnect();

        $this->expectException(ConnectionException::class);
        $this->serverConnectionFake->establish();
    }

    /** @test */
    public function it_can_assert_command_ran(): void
    {
        $this->serverConnectionFake->establish()->run('ls -la');

        $this->serverConnectionFake->assertCommandRan('ls -la');
        $this->expectException(ExpectationFailedException::class);
        $this->serverConnectionFake->assertCommandRan('non-existent-command');
    }

    /** @test */
    public function it_can_assert_file_uploaded(): void
    {
        $this->serverConnectionFake->establish()->upload('/local/path', '/remote/path');

        $this->serverConnectionFake->assertFileUploaded('/local/path', '/remote/path');
        $this->expectException(ExpectationFailedException::class);
        $this->serverConnectionFake->assertFileUploaded('/wrong/path', '/remote/path');
    }

    /** @test */
    public function it_can_assert_file_downloaded(): void
    {
        $this->serverConnectionFake->establish()->download('/remote/path', '/local/path');

        $this->serverConnectionFake->assertFileDownloaded('/remote/path', '/local/path');
        $this->expectException(ExpectationFailedException::class);
        $this->serverConnectionFake->assertFileDownloaded('/wrong/path', '/local/path');
    }

    /** @test */
    public function it_can_assert_output(): void
    {
        $this->serverConnectionFake->setOutput('Command output');
        $output = $this->serverConnectionFake->establish()->run('some-command');

        $this->assertEquals('Command output', $output);
        $this->serverConnectionFake->assertOutput('Command output');
        $this->expectException(ExpectationFailedException::class);
        $this->serverConnectionFake->assertOutput('Wrong output');
    }

    /** @test */
    public function it_can_disconnect(): void
    {
        $connection = $this->serverConnectionFake->establish();
        $this->serverConnectionFake->assertConnected();

        $connection->disconnect();
        $this->serverConnectionFake->assertDisconnected();
    }

    /** @test */
    public function it_fails_assert_disconnected_when_still_connected(): void
    {
        $this->serverConnectionFake->establish();

        $this->expectException(ExpectationFailedException::class);
        $this->serverConnectionFake->assertDisconnected();
    }

    /** @test */
    public function it_fails_assert_connected_when_disconnected(): void
    {
        $connection = $this->serverConnectionFake->establish();
        $connection->disconnect();

        $this->expectException(ExpectationFailedException::class);
        $this->serverConnectionFake->assertConnected();
    }

    /** @test */
    public function it_cannot_run_commands_after_disconnect(): void
    {
        $connection = $this->serverConnectionFake->establish();
        $connection->disconnect();

        $this->expectException(RuntimeException::class);
        $connection->run('ls -la');
    }

    /** @test */
    public function it_cannot_upload_after_disconnect(): void
    {
        $connection = $this->serverConnectionFake->establish();
        $connection->disconnect();

        $this->expectException(RuntimeException::class);
        $connection->upload('/local/path', '/remote/path');
    }

    /** @test */
    public function it_cannot_download_after_disconnect(): void
    {
        $connection = $this->serverConnectionFake->establish();
        $connection->disconnect();

        $this->expectException(RuntimeException::class);
        $connection->download('/remote/path', '/local/path');
    }

    /** @test */
    public function it_can_assert_not_connected(): void
    {
        $this->serverConnectionFake->assertNotConnected();

        $this->serverConnectionFake->establish();
        $this->expectException(ExpectationFailedException::class);
        $this->serverConnectionFake->assertNotConnected();
    }

    /** @test */
    public function it_can_check_if_connected(): void
    {
        $this->assertFalse($this->serverConnectionFake->isConnected());

        $this->serverConnectionFake->establish();
        $this->assertTrue($this->serverConnectionFake->isConnected());

        $this->serverConnectionFake->disconnect();
        $this->assertFalse($this->serverConnectionFake->isConnected());
    }
}
