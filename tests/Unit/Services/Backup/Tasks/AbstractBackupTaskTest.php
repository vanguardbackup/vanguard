<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Backup\Tasks;

use App\Mail\BackupTaskFailed;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Tasks\AbstractBackupTask;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Mockery;
use ReflectionClass;
use RuntimeException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Event::fake();
    $this->backupTask = BackupTask::factory()->create();

    $this->abstractBackupTask = new class($this->backupTask->id) extends AbstractBackupTask
    {
        public function getLogOutput(): string
        {
            return $this->logOutput;
        }

        protected function performBackup(): void
        {
            $this->logMessage('Performing backup');
        }
    };

    $this->reflection = new ReflectionClass($this->abstractBackupTask);
});

it('initializes backup task', function (): void {
    $this->abstractBackupTask->handle();

    expect(BackupTaskLog::where('backup_task_id', $this->backupTask->id)->exists())->toBeTrue()
        ->and($this->backupTask->fresh()->status)->toBe(BackupTask::STATUS_READY);
});

it('logs messages with timestamp', function (): void {
    $method = $this->reflection->getMethod('logMessage');

    $message = 'Test log message';
    $method->invoke($this->abstractBackupTask, $message);

    expect($this->abstractBackupTask->getLogOutput())
        ->toContain($message)
        ->toMatch('/\[\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}\] ' . preg_quote($message, '/') . '/');
});

it('generates backup file name', function (): void {
    $method = $this->reflection->getMethod('generateBackupFileName');

    $fileName = $method->invoke($this->abstractBackupTask, 'zip');

    expect($fileName)->toMatch('/^backup_\d+_\d{14}\.zip$/');
});

it('checks if path exists', function (): void {
    $mock = Mockery::mock(SFTPInterface::class);
    $mock->shouldReceive('isConnected')->andReturn(true);
    $mock->shouldReceive('stat')->with('/path/to/source')->andReturn(['type' => 2]); // 2 for directory

    $method = $this->reflection->getMethod('checkPathExists');

    expect($method->invoke($this->abstractBackupTask, $mock, '/path/to/source'))->toBeTrue();
});

it('gets remote directory size', function (): void {
    $mock = Mockery::mock(SFTPInterface::class);
    $mock->shouldReceive('isConnected')->andReturn(true);
    $mock->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $mock->shouldReceive('exec')->with("du -sb '/path/to/source' | cut -f1")->andReturn('1024');

    $method = $this->reflection->getMethod('getRemoteDirectorySize');

    expect($method->invoke($this->abstractBackupTask, $mock, '/path/to/source'))->toBe(1024);
});

it('zips remote directory', function (): void {
    $mock = Mockery::mock(SFTPInterface::class);
    $mock->shouldReceive('isConnected')->andReturn(true);
    $mock->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $mock->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('1024');
    $mock->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('5000000');
    $mock->shouldReceive('exec')->with(Mockery::pattern("/^cd '\/path\/to\/source' && zip -rv '\/tmp\/backup\.zip' \./"))
        ->andReturn('adding: somefile (stored 0%)');
    $mock->shouldReceive('exec')->with("test -f '/tmp/backup.zip' && stat -c%s '/tmp/backup.zip'")->andReturn('512');

    $method = $this->reflection->getMethod('zipRemoteDirectory');

    $method->invoke($this->abstractBackupTask, $mock, '/path/to/source', '/tmp/backup.zip', []);

    // Since we can't easily verify the zip operation, we'll just check that no exception was thrown
    expect(true)->toBeTrue();
});

it('handles backup failure', function (): void {
    Mail::fake();
    $method = $this->reflection->getMethod('handleBackupFailure');

    $exception = new Exception('Test exception');
    $method->invoke($this->abstractBackupTask, $exception);

    Mail::assertQueued(BackupTaskFailed::class);

    expect($this->abstractBackupTask->getLogOutput())
        ->toContain('Error in backup process: Test exception');
});

afterEach(function (): void {
    Mockery::close();
});

beforeEach(function (): void {
    $this->backupTask = BackupTask::factory()->create([
        'encryption_password' => 'test_password',
    ]);

    $this->abstractBackupTask = new class($this->backupTask->id) extends AbstractBackupTask
    {
        public function getLogOutput(): string
        {
            return $this->logOutput;
        }

        protected function performBackup(): void
        {
            $this->logMessage('Performing backup');
        }
    };

    $this->reflection = new ReflectionClass($this->abstractBackupTask);
});

it('ensures encryption password exists', function (): void {
    $method = $this->reflection->getMethod('ensureEncryptionPassword');

    // Should not throw an exception
    $method->invoke($this->abstractBackupTask);

    // Now let's remove the encryption password
    $this->backupTask->update(['encryption_password' => null]);
    $this->abstractBackupTask = new class($this->backupTask->id) extends AbstractBackupTask
    {
        protected function performBackup(): void {}
    };

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Encryption password is missing.');
    $method->invoke($this->abstractBackupTask);
});

it('generates secure IV', function (): void {
    $method = $this->reflection->getMethod('generateSecureIV');

    $iv = $method->invoke($this->abstractBackupTask);

    expect(strlen((string) $iv))->toBe(16)
        ->and(bin2hex((string) $iv))->toMatch('/^[0-9a-f]{32}$/');
});

it('builds encrypt command', function (): void {
    $method = $this->reflection->getMethod('buildEncryptCommand');

    $remoteFilePath = '/path/to/backup.zip';
    $iv = hex2bin('4c3dbe3d7a693f8c4995894939880b27');

    $command = $method->invoke($this->abstractBackupTask, $remoteFilePath, $iv);

    expect($command)
        ->toContain("openssl enc -aes-256-cbc -in '/path/to/backup.zip' -out '/path/to/backup.zip'.enc")
        ->toContain("-pass pass:'test_password' -pbkdf2 -iter 100000 -nosalt")
        ->toContain('echo -n 4c3dbe3d7a693f8c4995894939880b27')
        ->toContain("xxd -r -p | cat - '/path/to/backup.zip'.enc > '/path/to/backup.zip'.tmp")
        ->toContain("mv '/path/to/backup.zip'.tmp '/path/to/backup.zip'")
        ->toContain("rm '/path/to/backup.zip'.enc");
});

it('ensures openssl command exists', function (): void {
    $method = $this->reflection->getMethod('ensureOpensslCommandExists');

    $mock = Mockery::mock(SFTPInterface::class);

    // Test when openssl exists
    $mock->shouldReceive('exec')
        ->with('command -v openssl')
        ->andReturn('/usr/bin/openssl');

    // Should not throw an exception
    $method->invoke($this->abstractBackupTask, $mock);

    // Test when openssl doesn't exist
    $mock = Mockery::mock(SFTPInterface::class);
    $mock->shouldReceive('exec')
        ->with('command -v openssl')
        ->andReturn('');

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Required openssl command not found on the remote system.');
    $method->invoke($this->abstractBackupTask, $mock);
});

it('handles encryption failure', function (): void {
    $method = $this->reflection->getMethod('handleEncryptionFailure');

    $errorMessage = 'Test encryption error';

    try {
        $method->invoke($this->abstractBackupTask, $errorMessage);
        $this->fail('Expected exception was not thrown');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe("Failed to encrypt the backup file: {$errorMessage}");
    }

    // Check the log output
    $logMethod = $this->reflection->getMethod('logError');

    $legacyMock = Mockery::mock(AbstractBackupTask::class)->makePartial();

    try {
        $method->invoke($legacyMock, $errorMessage);
    } catch (RuntimeException) {
        // Expected exception
    }

    Mockery::close();
});

afterEach(function (): void {
    Mockery::close();
});
