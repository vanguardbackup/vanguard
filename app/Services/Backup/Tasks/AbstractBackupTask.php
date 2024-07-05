<?php

declare(strict_types=1);

namespace App\Services\Backup\Tasks;

use App\Exceptions\DatabaseDumpException;
use App\Exceptions\SFTPConnectionException;
use App\Models\BackupTask as BackupTaskModel;
use App\Models\BackupTaskLog;
use App\Services\Backup\Backup;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;

abstract class AbstractBackupTask extends Backup
{
    protected BackupTaskModel $backupTask;
    protected BackupTaskLog $backupTaskLog;
    protected string $logOutput = '';
    protected float $scriptRunTime;
    protected ?int $backupSize = null;

    public function __construct(int $backupTaskId)
    {
        parent::__construct();
        $this->scriptRunTime = microtime(true);
        $this->backupTask = $this->obtainBackupTask($backupTaskId);
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        Log::info("Starting backup task: {$this->backupTask->id}");

        $this->initializeBackup();

        try {
            $this->performBackup();
            $this->finalizeSuccessfulBackup();
        } catch (DatabaseDumpException|SFTPConnectionException|RuntimeException $exception) {
            $this->handleBackupFailure($exception);
        } catch (Exception $exception) {
            $this->handleBackupFailure($exception);
            throw new RuntimeException('Unexpected error during backup: ' . $exception->getMessage(), 0, $exception);
        } finally {
            $this->cleanupBackup();
        }
    }

    public function generateBackupFileName(string $extension): string
    {
        $prefix = $this->backupTask->hasFileNameAppended() ? $this->backupTask->appended_file_name . '_' : '';

        return "{$prefix}backup_{$this->backupTask->id}_" . Carbon::now()->format('YmdHis') . ".{$extension}";
    }

    abstract protected function performBackup(): void;

    /**
     * @throws Exception
     */
    protected function initializeBackup(): void
    {
        $this->backupTask->setScriptUpdateTime();
        $this->backupTaskLog = $this->recordBackupTaskLog($this->backupTask->id, $this->logOutput);
        $this->updateBackupTaskStatus($this->backupTask, BackupTaskModel::STATUS_RUNNING);
        $this->logMessage('Backup task started.');
        $this->updateBackupTaskLogOutput($this->backupTaskLog, $this->logOutput);
    }

    /**
     * @throws Exception
     */
    protected function finalizeSuccessfulBackup(): void
    {
        $this->logMessage('Backup task has finished successfully!');
        $this->backupTaskLog->setSuccessfulTime();
        $this->updateBackupTaskLogOutput($this->backupTaskLog, $this->logOutput);
    }

    protected function handleBackupFailure(Exception $exception): void
    {
        $this->logOutput .= 'Error in backup process: ' . $exception->getMessage() . "\n";
        $this->sendEmailNotificationOfTaskFailure($this->backupTask, $exception->getMessage());
        Log::error("Error in backup process for task {$this->backupTask->id}: " . $exception->getMessage(), ['exception' => $exception]);
    }

    protected function cleanupBackup(): void
    {
        $this->updateBackupTaskLogOutput($this->backupTaskLog, $this->logOutput);
        $this->backupTaskLog->setFinishedTime();
        $this->updateBackupTaskStatus($this->backupTask, BackupTaskModel::STATUS_READY);
        $this->backupTask->sendNotifications();
        $this->backupTask->updateLastRanAt();
        $this->backupTask->resetScriptUpdateTime();

        $elapsedTime = microtime(true) - $this->scriptRunTime;
        $this->backupTask->data()->create([
            'duration' => $elapsedTime,
            'size' => $this->backupSize,
        ]);

        Log::info("Completed backup task: {$this->backupTask->label} ({$this->backupTask->id}).");
    }

    /**
     * @throws Exception
     */
    protected function logWithTimestamp(string $message, string $timezone): string
    {
        $timestampedMessage = parent::logWithTimestamp($message, $timezone);
        $this->logOutput .= $timestampedMessage;

        return $timestampedMessage;
    }

    /**
     * @throws Exception
     */
    protected function logMessage(string $message): void
    {
        $this->logWithTimestamp($message, $this->backupTask->user->timezone);
    }
}
