<?php

declare(strict_types=1);

use App\Services\Backup\Adapters\SFTPAdapter;
use phpseclib3\Net\SFTP;

uses()->group('sftp-adapter');

beforeEach(function () {
    $this->mockSFTP = Mockery::mock(SFTP::class);
    $this->adapter = new SFTPAdapter('localhost');

    $reflection = new ReflectionClass($this->adapter);
    $property = $reflection->getProperty('sftp');
    $property->setValue($this->adapter, $this->mockSFTP);
});

afterEach(function () {
    Mockery::close();
});

it('logs in successfully', function () {
    $this->mockSFTP->shouldReceive('login')->once()->with('username', 'password')->andReturn(true);
    expect($this->adapter->login('username', 'password'))->toBeTrue();
});

it('gets last error', function () {
    $this->mockSFTP->shouldReceive('getLastError')->once()->andReturn('Test error');
    expect($this->adapter->getLastError())->toBe('Test error');
});

it('executes command', function () {
    $this->mockSFTP->shouldReceive('exec')->once()->with('ls -l')->andReturn('command output');
    expect($this->adapter->exec('ls -l'))->toBe('command output');
});

it('checks connection status', function () {
    $this->mockSFTP->shouldReceive('isConnected')->once()->andReturn(true);
    expect($this->adapter->isConnected())->toBeTrue();
});

it('puts file', function () {
    $this->mockSFTP->shouldReceive('put')->once()->with('/remote/file', 'file content', SFTP::SOURCE_STRING)->andReturn(true);
    expect($this->adapter->put('/remote/file', 'file content'))->toBeTrue();
});

it('gets file', function () {
    $this->mockSFTP->shouldReceive('get')->once()->with('/remote/file', false)->andReturn('file content');
    expect($this->adapter->get('/remote/file'))->toBe('file content');
});

it('deletes file', function () {
    $this->mockSFTP->shouldReceive('delete')->once()->with('/remote/file', true)->andReturn(true);
    expect($this->adapter->delete('/remote/file'))->toBeTrue();
});

it('creates directory', function () {
    $this->mockSFTP->shouldReceive('mkdir')->once()->with('/remote/dir', -1, false)->andReturn(true);
    expect($this->adapter->mkdir('/remote/dir'))->toBeTrue();
});

it('changes file permissions', function () {
    $this->mockSFTP->shouldReceive('chmod')->once()->with(0644, '/remote/file', false)->andReturn(true);
    expect($this->adapter->chmod(0644, '/remote/file'))->toBeTrue();
});

it('gets file stats', function () {
    $stats = ['size' => 1024, 'mtime' => time()];
    $this->mockSFTP->shouldReceive('stat')->once()->with('/remote/file')->andReturn($stats);
    expect($this->adapter->stat('/remote/file'))->toBe($stats);
});
