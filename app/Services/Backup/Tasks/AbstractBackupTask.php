<?php

namespace App\Services\Backup\Tasks;

use App\Models\BackupTask as BackupTaskModel;
use App\Models\BackupTaskLog;
use App\Services\Backup\Backup;
use Exception;
use Illuminate\Support\Facades\Log;

abstract class AbstractBackupTask extends Backup
{
    /**
     * @var BackupTaskModel
     */
    protected BackupTaskModel $backupTask;
    /**
     * @var BackupTaskLog
     */
    protected BackupTaskLog $backupTaskLog;
    /**
     * @var string
     */
    protected string $logOutput = '';
    /**
     * @var float
     */
    protected float $scriptRunTime;
    /**
     * @var int|null
     */
    protected ?int $backupSize = null;

    /**
     * @param int $backupTaskId
     */
    public function __construct(int $backupTaskId)
    {
        parent::__construct();
        $this->scriptRunTime = microtime(true);
        $this->backupTask = $this->obtainBackupTask($backupTaskId);
    }

    /**
     * @return void
     */
    abstract protected function performBackup(): void;

    /**
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        Log::info("Starting backup task: {$this->backupTask->id}");

        $this->initializeBackup();

        try {
            $this->performBackup();
            $this->finalizeSuccessfulBackup();
        } catch (Exception $exception) {
            $this->handleBackupFailure($exception);
        } finally {
            $this->cleanupBackup();
        }
    }

    /**
     * @return void
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
     * @return void
     * @throws Exception
     */
    protected function finalizeSuccessfulBackup(): void
    {
        $this->logMessage('Backup task has finished successfully!');
        $this->backupTaskLog->setSuccessfulTime();
        $this->updateBackupTaskLogOutput($this->backupTaskLog, $this->logOutput);
    }

    /**
     * @param Exception $exception
     * @return void
     */
    protected function handleBackupFailure(Exception $exception): void
    {
        $this->logOutput .= 'Error in backup process: ' . $exception->getMessage() . "\n";
        Log::error("Error in backup process for task {$this->backupTask->id}: " . $exception->getMessage(), ['exception' => $exception]);
    }

    /**
     * @return void
     */
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
     * @param string $message
     * @param string $timezone
     * @return string
     * @throws Exception
     */
    protected function logWithTimestamp(string $message, string $timezone): string
    {
        $timestampedMessage = parent::logWithTimestamp($message, $timezone);
        $this->logOutput .= $timestampedMessage;
        return $timestampedMessage;
    }

    /**
     * @param string $message
     * @return void
     * @throws Exception
     */
    protected function logMessage(string $message): void
    {
        $this->logWithTimestamp($message, $this->backupTask->user->timezone);
    }

    /**
     * @param string $extension
     * @return string
     */
    protected function generateBackupFileName(string $extension): string
    {
        $prefix = $this->backupTask->hasFileNameAppended() ? $this->backupTask->appended_file_name . '_' : '';
        return "{$prefix}backup_{$this->backupTask->id}_" . date('YmdHis') . ".{$extension}";
    }
}