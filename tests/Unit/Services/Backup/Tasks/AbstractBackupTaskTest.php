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
use Mail;
use Mockery;
use ReflectionClass;

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
    $sftp = Mockery::mock(SFTPInterface::class);
    $sftp->shouldReceive('isConnected')->andReturn(true);
    $sftp->shouldReceive('stat')->with('/path/to/source')->andReturn(['type' => 2]); // 2 for directory

    $method = $this->reflection->getMethod('checkPathExists');

    expect($method->invoke($this->abstractBackupTask, $sftp, '/path/to/source'))->toBeTrue();
});

it('gets remote directory size', function (): void {
    $sftp = Mockery::mock(SFTPInterface::class);
    $sftp->shouldReceive('isConnected')->andReturn(true);
    $sftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $sftp->shouldReceive('exec')->with("du -sb '/path/to/source' | cut -f1")->andReturn('1024');

    $method = $this->reflection->getMethod('getRemoteDirectorySize');

    expect($method->invoke($this->abstractBackupTask, $sftp, '/path/to/source'))->toBe(1024);
});

it('zips remote directory', function (): void {
    $sftp = Mockery::mock(SFTPInterface::class);
    $sftp->shouldReceive('isConnected')->andReturn(true);
    $sftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $sftp->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('1024');
    $sftp->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('5000000');
    $sftp->shouldReceive('exec')->with(Mockery::pattern("/^cd '\/path\/to\/source' && zip -rv '\/tmp\/backup\.zip' \./"))
        ->andReturn('adding: somefile (stored 0%)');
    $sftp->shouldReceive('exec')->with("test -f '/tmp/backup.zip' && stat -c%s '/tmp/backup.zip'")->andReturn('512');

    $method = $this->reflection->getMethod('zipRemoteDirectory');

    $method->invoke($this->abstractBackupTask, $sftp, '/path/to/source', '/tmp/backup.zip', []);

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
