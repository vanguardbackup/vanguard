<?php

declare(strict_types=1);

use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Destinations\Contracts\BackupDestinationInterface;

uses()->group('backup-destination-interface');

beforeEach(function (): void {
    $this->backupDestination = Mockery::mock(BackupDestinationInterface::class);
    $this->mockSftp = Mockery::mock(SFTPInterface::class);
    $this->remoteZipPath = '/remote/path/backup.zip';
    $this->fileName = 'backup.zip';
    $this->storagePath = 'storage/backups';

    $this->expectStreamFiles = function (bool $success, int $retries = 3, int $delay = 5): void {
        $this->backupDestination->shouldReceive('streamFiles')
            ->with($this->mockSftp, $this->remoteZipPath, $this->fileName, $this->storagePath, $retries, $delay)
            ->once()
            ->andReturn($success);
    };

    $this->callStreamFiles = function (int $retries = 3, int $delay = 5): bool {
        return $this->backupDestination->streamFiles(
            $this->mockSftp,
            $this->remoteZipPath,
            $this->fileName,
            $this->storagePath,
            $retries,
            $delay
        );
    };
});

afterEach(function (): void {
    Mockery::close();
});

it('can list files', function (): void {
    $pattern = 'backup_*';
    $expectedFiles = ['backup_1.zip', 'backup_2.zip'];

    $this->backupDestination->shouldReceive('listFiles')
        ->with($pattern)
        ->once()
        ->andReturn($expectedFiles);

    $result = $this->backupDestination->listFiles($pattern);

    expect($result)->toBe($expectedFiles);
});

it('can delete a file', function (): void {
    $filePath = 'backup_1.zip';

    $this->backupDestination->shouldReceive('deleteFile')
        ->with($filePath)
        ->once();

    $this->backupDestination->deleteFile($filePath);

    // If we get here without exceptions, the test passes
    expect(true)->toBeTrue();
});

it('can stream files', function (): void {
    ($this->expectStreamFiles)(true);
    $result = ($this->callStreamFiles)();
    expect($result)->toBeTrue();
});

it('handles file streaming failure', function (): void {
    ($this->expectStreamFiles)(false);
    $result = ($this->callStreamFiles)();
    expect($result)->toBeFalse();
});

it('respects custom retry parameters for streaming', function (): void {
    $customRetries = 5;
    $customDelay = 10;

    ($this->expectStreamFiles)(true, $customRetries, $customDelay);
    $result = ($this->callStreamFiles)($customRetries, $customDelay);
    expect($result)->toBeTrue();
});
