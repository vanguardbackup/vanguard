<?php

use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Traits\BackupHelpers;
use Illuminate\Support\Facades\Log;

class BackupHelpersTest
{
    use BackupHelpers;

    public function publicDownloadFileViaSFTP(SFTPInterface $sftp, string $remoteZipPath): string
    {
        return $this->downloadFileViaSFTP($sftp, $remoteZipPath);
    }

    public function publicOpenFileAsStream(string $tempFile): mixed
    {
        return $this->openFileAsStream($tempFile);
    }

    public function publicCleanUpTempFile(string $tempFile): void
    {
        $this->cleanUpTempFile($tempFile);
    }

    public function publicLogInfo(string $message, array $context = []): void
    {
        $this->logInfo($message, $context);
    }

    public function publicLogDebug(string $message, array $context = []): void
    {
        $this->logDebug($message, $context);
    }

    public function publicLogWarning(string $message, array $context = []): void
    {
        $this->logWarning($message, $context);
    }

    public function publicLogError(string $message, array $context = []): void
    {
        $this->logError($message, $context);
    }

    public function publicLogCritical(string $message, array $context = []): void
    {
        $this->logCritical($message, $context);
    }
}

beforeEach(function () {
    $this->helperClass = new BackupHelpersTest;
});

it('retries command until success', function () {
    $attempts = 0;
    $command = function () use (&$attempts) {
        $attempts++;

        return $attempts === 3;
    };

    $result = $this->helperClass->retryCommand($command, 5, 0);

    expect($result)->toBeTrue()
        ->and($attempts)->toBe(3);
});

it('retries command until max attempts', function () {
    $attempts = 0;
    $command = function () use (&$attempts) {
        $attempts++;

        return false;
    };

    $result = $this->helperClass->retryCommand($command, 5, 0);

    expect($result)->toBeFalse()
        ->and($attempts)->toBe(5);
});

it('downloads file via SFTP', function () {
    $mockSftp = Mockery::mock(SFTPInterface::class);
    $mockSftp->shouldReceive('get')->andReturn(true);
    $mockSftp->shouldReceive('getLastError')->andReturn('');

    $result = $this->helperClass->publicDownloadFileViaSFTP($mockSftp, '/remote/path');

    expect($result)->toBeString()
        ->and(file_exists($result))->toBeTrue();
    unlink($result);
});

it('throws exception when SFTP download fails', function () {
    $mockSftp = Mockery::mock(SFTPInterface::class);
    $mockSftp->shouldReceive('get')->andReturn(false);
    $mockSftp->shouldReceive('getLastError')->andReturn('SFTP error');

    $this->helperClass->publicDownloadFileViaSFTP($mockSftp, '/remote/path');
})->throws(Exception::class, 'Failed to download the remote file: SFTP error');

it('opens file as stream', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tempFile, 'test content');

    $stream = $this->helperClass->publicOpenFileAsStream($tempFile);

    expect($stream)->toBeResource();
    fclose($stream);
    unlink($tempFile);
});

it('throws exception when file cannot be opened as stream', function () {
    $this->helperClass->publicOpenFileAsStream('/non/existent/file');
})->throws(Exception::class);

it('cleans up temp file', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'test');

    $this->helperClass->publicCleanUpTempFile($tempFile);

    expect(file_exists($tempFile))->toBeFalse();
});

it('logs messages at different levels', function () {
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
