<?php

declare(strict_types=1);

use App\Services\Backup\Contracts\SFTPInterface;
use Illuminate\Support\Facades\Log;
use Tests\Unit\Services\Backup\Traits\BackupHelpersTestClass;

beforeEach(function (): void {
    $this->helperClass = new BackupHelpersTestClass;
});

it('retries command until success', function (): void {
    $attempts = 0;
    $command = function () use (&$attempts): bool {
        $attempts++;

        return $attempts === 3;
    };

    $result = $this->helperClass->retryCommand($command, 5, 0);

    expect($result)->toBeTrue()
        ->and($attempts)->toBe(3);
});

it('retries command until max attempts', function (): void {
    $attempts = 0;
    $command = function () use (&$attempts): bool {
        $attempts++;

        return false;
    };

    $result = $this->helperClass->retryCommand($command, 5, 0);

    expect($result)->toBeFalse()
        ->and($attempts)->toBe(5);
});

it('downloads file via SFTP', function (): void {
    $mockSftp = Mockery::mock(SFTPInterface::class);
    $mockSftp->shouldReceive('get')->andReturn(true);
    $mockSftp->shouldReceive('getLastError')->andReturn('');

    $result = $this->helperClass->publicDownloadFileViaSFTP($mockSftp, '/remote/path');

    expect($result)->toBeString()
        ->and(file_exists($result))->toBeTrue();
    unlink($result);
});

it('throws exception when SFTP download fails', function (): void {
    $mockSftp = Mockery::mock(SFTPInterface::class);
    $mockSftp->shouldReceive('get')->andReturn(false);
    $mockSftp->shouldReceive('getLastError')->andReturn('SFTP error');

    $this->helperClass->publicDownloadFileViaSFTP($mockSftp, '/remote/path');
})->throws(Exception::class, 'Failed to download the remote file: SFTP error');

it('opens file as stream', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tempFile, 'test content');

    $stream = $this->helperClass->publicOpenFileAsStream($tempFile);

    expect($stream)->toBeResource();
    fclose($stream);
    unlink($tempFile);
});

it('throws exception when file cannot be opened as stream', function (): void {
    $this->helperClass->publicOpenFileAsStream('/non/existent/file');
})->throws(Exception::class);

it('cleans up temp file', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'test');

    $this->helperClass->publicCleanUpTempFile($tempFile);

    expect(file_exists($tempFile))->toBeFalse();
});

it('logs messages at different levels', function (): void {
    Log::shouldReceive('info')->once()->with('Info message', ['context' => 'test']);
    Log::shouldReceive('debug')->once()->with('Debug message', ['context' => 'test']);
    Log::shouldReceive('warning')->once()->with('Warning message', ['context' => 'test']);
    Log::shouldReceive('error')->once()->with('Error message', ['context' => 'test']);
    Log::shouldReceive('critical')->once()->with('Critical message', ['context' => 'test']);

    $this->helperClass->publicLogInfo('Info message', ['context' => 'test']);
    $this->helperClass->publicLogDebug('Debug message', ['context' => 'test']);
    $this->helperClass->publicLogWarning('Warning message', ['context' => 'test']);
    $this->helperClass->publicLogError('Error message', ['context' => 'test']);
    $this->helperClass->publicLogCritical('Critical message', ['context' => 'test']);
});
