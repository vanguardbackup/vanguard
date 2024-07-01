<?php

declare(strict_types=1);

namespace App\Services\Backup\Tasks;

use App\Exceptions\BackupTaskZipException;
use App\Exceptions\SFTPConnectionException;
use App\Models\BackupTask;
use App\Services\Backup\Backup;
use App\Services\Backup\BackupConstants;
use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FileBackup extends Backup
{
    /**
     * Handle the file backup process.
     *
     * @throws Exception
     */
    public function handle(int $backupTaskId): void
    {
        Log::info("Starting backup task: {$backupTaskId}");

        $scriptRunTime = microtime(true);

        $backupTask = $this->obtainBackupTask($backupTaskId);
        $backupTask->setScriptUpdateTime();

        $logOutput = '';
        $backupTaskLog = $this->recordBackupTaskLog($backupTaskId, $logOutput);

        try {
            $this->performBackup($backupTask, $backupTaskLog);
        } catch (Exception $exception) {
            $this->handleBackupException($exception, $backupTaskLog);
        } finally {
            $this->finalizeBackupTask($backupTask, $backupTaskLog, $scriptRunTime);
        }
    }

    /**
     * Perform the backup process.
     *
     * @param  mixed  $backupTaskLog
     *
     * @throws Exception
     */
    private function performBackup(BackupTask $backupTask, $backupTaskLog): void
    {
        $this->updateBackupTaskStatus($backupTask, BackupTask::STATUS_RUNNING);

        $sftp = $this->establishSFTPConnection($backupTask->remoteServer, $backupTask);
        $logOutput = $this->logWithTimestamp('SSH Connection established to the server.', $backupTask->user->timezone);
        $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

        $sourcePath = $backupTask->getAttributeValue('source_path');
        $this->validateSourcePath($sftp, $sourcePath, $backupTask, $backupTaskLog);

        $this->checkDirectorySize($sftp, $sourcePath, $backupTask, $backupTaskLog);

        $zipFileName = $this->createZipFile($sftp, $sourcePath, $backupTask);
        $logOutput .= $this->logWithTimestamp("Directory has been zipped: {$zipFileName}", $backupTask->user->timezone);
        $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

        $this->uploadBackup($backupTask, $sftp, $zipFileName, $backupTaskLog);

        $this->rotateBackupsIfNeeded($backupTask);

        $this->cleanupTemporaryFiles($sftp, $zipFileName);
        $logOutput .= $this->logWithTimestamp('Cleaned up the temporary zip file on server.', $backupTask->user->timezone);
        $logOutput .= $this->logWithTimestamp('Backup task has finished successfully!', $backupTask->user->timezone);
        $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);
    }

    /**
     * Validate the source path.
     *
     * @throws SFTPConnectionException
     */
    private function validateSourcePath($sftp, string $sourcePath, BackupTask $backupTask, $backupTaskLog): void
    {
        if (! $this->checkPathExists($sftp, $sourcePath)) {
            $errorMessage = $this->logWithTimestamp('The path specified does not exist.', $backupTask->user->timezone);
            $this->handleFailure($backupTask, $errorMessage, $errorMessage);
            throw new SFTPConnectionException('The path specified does not exist.');
        }
    }

    /**
     * Check the directory size.
     *
     * @throws BackupTaskZipException|SFTPConnectionException
     */
    private function checkDirectorySize($sftp, string $sourcePath, BackupTask $backupTask, $backupTaskLog): int
    {
        $dirSize = $this->getRemoteDirectorySize($sftp, $sourcePath);
        $dirSizeInMB = number_format($dirSize / 1024 / 1024, 1);
        $logOutput = $this->logWithTimestamp("Directory size of {$sourcePath}: {$dirSizeInMB} MB.", $backupTask->user->timezone);
        $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

        if ($dirSize > BackupConstants::FILE_SIZE_LIMIT) {
            $errorMessage = $this->logWithTimestamp('Directory size exceeds the limit.', $backupTask->user->timezone);
            $this->handleFailure($backupTask, $logOutput, $errorMessage);
            throw new BackupTaskZipException('Directory size exceeds the limit.');
        }

        return $dirSize;
    }

    /**
     * Create zip file.
     *
     * @throws BackupTaskZipException|SFTPConnectionException
     */
    private function createZipFile($sftp, string $sourcePath, BackupTask $backupTask): string
    {
        $excludeDirs = [];
        if ($this->isLaravelDirectory($sftp, $sourcePath)) {
            $excludeDirs = ['node_modules', 'vendor'];
        }

        $zipFileName = $backupTask->hasFileNameAppended()
            ? $backupTask->appended_file_name . '_backup_' . $backupTask->id . '_' . date('YmdHis') . '.zip'
            : 'backup_' . $backupTask->id . '_' . date('YmdHis') . '.zip';

        $remoteZipPath = "/tmp/{$zipFileName}";
        $this->zipRemoteDirectory($sftp, $sourcePath, $remoteZipPath, $excludeDirs);

        return $zipFileName;
    }

    /**
     * Upload backup to destination.
     *
     * @throws Exception
     */
    private function uploadBackup(BackupTask $backupTask, $sftp, string $zipFileName, $backupTaskLog): void
    {
        $remoteZipPath = "/tmp/{$zipFileName}";
        $backupDestinationModel = $backupTask->backupDestination;
        $storagePath = $backupTask->getAttributeValue('store_path');

        if (! $this->backupDestinationDriver($backupDestinationModel->type, $sftp, $remoteZipPath, $backupDestinationModel, $zipFileName, $storagePath)) {
            $errorMessage = $this->logWithTimestamp('Failed to upload the zip file to destination.', $backupTask->user->timezone);
            $this->handleFailure($backupTask, $errorMessage, $errorMessage);
            throw new RuntimeException('Failed to upload the zip file to destination.');
        }

        $logOutput = $this->logWithTimestamp("Backup has been uploaded to {$backupDestinationModel->label} - {$backupDestinationModel->type()}: {$zipFileName}", $backupTask->user->timezone);
        $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);
    }

    /**
     * Rotate old backups if needed.
     */
    private function rotateBackupsIfNeeded(BackupTask $backupTask): void
    {
        if ($backupTask->isRotatingBackups()) {
            $backupDestination = $this->createBackupDestinationInstance($backupTask->backupDestination);
            $this->rotateOldBackups($backupDestination, $backupTask->id, $backupTask->maximum_backups_to_keep);
        }
    }

    /**
     * Clean up temporary files.
     */
    private function cleanupTemporaryFiles($sftp, string $zipFileName): void
    {
        $remoteZipPath = "/tmp/{$zipFileName}";
        $sftp->delete($remoteZipPath);
    }

    /**
     * Handle exceptions during the backup process.
     */
    private function handleBackupException(Exception $exception, $backupTaskLog): void
    {
        $logOutput = 'Error in backup process: ' . $exception->getMessage() . "\n";
        Log::error('Error in backup process: ' . $exception->getMessage(), ['exception' => $exception]);
        $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);
    }

    /**
     * Finalize the backup task.
     */
    private function finalizeBackupTask(BackupTask $backupTask, $backupTaskLog, float $scriptRunTime): void
    {
        $backupTaskLog->setFinishedTime();
        $this->updateBackupTaskStatus($backupTask, BackupTask::STATUS_READY);
        $backupTask->sendNotifications();
        $backupTask->updateLastRanAt();
        $backupTask->resetScriptUpdateTime();
        Log::info('[BACKUP TASK] Completed backup task: ' . $backupTask->label . ' (' . $backupTask->id . ').');

        $elapsedTime = microtime(true) - $scriptRunTime;
        $backupTask->data()->create([
            'duration' => $elapsedTime,
            'size' => $backupTaskLog->size ?? null,
        ]);
    }
}
