<?php

namespace App\Services\Backup\Tasks;

use App\Models\BackupTask;
use App\Services\Backup\Backup;
use App\Services\Backup\BackupConstants;
use Exception;
use Illuminate\Support\Facades\Log;

class FileBackup extends Backup
{
    public function handle(int $backupTaskId): void
    {
        Log::info("Starting backup task: {$backupTaskId}");

        $backupTask = $this->obtainBackupTask($backupTaskId);
        $backupTask->setScriptUpdateTime();
        $remoteServer = $backupTask->remoteServer;
        $backupDestinationModel = $backupTask->backupDestination;
        $sourcePath = $backupTask->getAttributeValue('source_path');
        $userTimezone = $backupTask->user->timezone;
        $storagePath = $backupTask->getAttributeValue('store_path');

        $logOutput = '';
        $backupTaskLog = $this->recordBackupTaskLog($backupTaskId, $logOutput);
        $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

        $this->updateBackupTaskStatus($backupTask, BackupTask::STATUS_RUNNING);

        $logOutput .= $this->logWithTimestamp('Backup task started.', $userTimezone);
        $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

        try {
            Log::info("Establishing SFTP connection for backup task: {$backupTaskId}");
            $sftp = $this->establishSFTPConnection($remoteServer, $backupTask);
            $logOutput .= $this->logWithTimestamp('SSH Connection established to the server.', $userTimezone);
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

            Log::info("Checking if source path exists: {$sourcePath} for backup task: {$backupTaskId}");
            if (! $this->checkPathExists($sftp, $sourcePath)) {
                $errorMessage = $this->logWithTimestamp('The path specified does not exist.', $userTimezone);
                Log::error("Source path does not exist: {$sourcePath} for backup task: {$backupTaskId}");
                $this->handleFailure($backupTask, $logOutput, $errorMessage);

                return;
            }

            $backupTask->setScriptUpdateTime();

            Log::info("Checking directory size for path: {$sourcePath} for backup task: {$backupTaskId}");
            $dirSize = $this->getRemoteDirectorySize($sftp, $sourcePath);
            $dirSizeInMB = number_format($dirSize / 1024 / 1024, 1);
            $logOutput .= $this->logWithTimestamp("Directory size of {$sourcePath}: {$dirSizeInMB} MB.", $userTimezone);
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

            if ($dirSize > BackupConstants::FILE_SIZE_LIMIT) {
                Log::error("Directory size exceeds limit: {$dirSize} bytes for path: {$sourcePath} for backup task: {$backupTaskId}");
                $this->handleFailure($backupTask, $logOutput, $this->logWithTimestamp('Directory size exceeds the limit.', $userTimezone));

                return;
            }

            if ($this->isLaravelDirectory($sftp, $sourcePath)) {
                Log::info("Laravel directory detected, deleting node_modules and vendor folders for path: {$sourcePath}");
                $this->deleteFolder($sftp, "{$sourcePath}/node_modules");
                $this->deleteFolder($sftp, "{$sourcePath}/vendor");
            }

            $zipFileName = $backupTask->hasFileNameAppended()
                ? $backupTask->appended_file_name . '_backup_' . $backupTaskId . '_' . date('YmdHis') . '.zip'
                : 'backup_' . $backupTaskId . '_' . date('YmdHis') . '.zip';

            $remoteZipPath = "/tmp/{$zipFileName}";
            Log::info("Zipping directory: {$sourcePath} to {$remoteZipPath} for backup task: {$backupTaskId}");
            $this->zipRemoteDirectory($sftp, $sourcePath, $remoteZipPath);
            $logOutput .= $this->logWithTimestamp("Directory has been zipped: {$remoteZipPath}", $userTimezone);
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

            $backupTask->setScriptUpdateTime();

            Log::info("Starting to stream zip file to backup destination for backup task: {$backupTaskId}");
            if (! $this->backupDestinationDriver($backupDestinationModel->type, $sftp, $remoteZipPath, $backupDestinationModel, $zipFileName, $storagePath)) {
                $errorMessage = $this->logWithTimestamp('Failed to upload the zip file to destination.', $userTimezone);
                Log::error("Failed to upload the zip file to destination for backup task: {$backupTaskId}. Remote zip path: {$remoteZipPath}, Backup destination: {$backupDestinationModel->label}, Filename: {$zipFileName}");
                $this->handleFailure($backupTask, $logOutput, $errorMessage);

                return;
            }

            if ($backupTask->isRotatingBackups()) {
                Log::info("Rotating old backups for backup task: {$backupTaskId}");
                $backupDestination = $this->createBackupDestinationInstance($backupDestinationModel);
                $this->rotateOldBackups($backupDestination, $backupTaskId, $backupTask->maximum_backups_to_keep, '.zip', 'backup_');
            }

            $logOutput .= $this->logWithTimestamp("Backup has been uploaded to {$backupDestinationModel->label} - {$backupDestinationModel->type()}: {$zipFileName}", $userTimezone);
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

            $sftp->delete($remoteZipPath);
            Log::info("Remote zip file deleted for backup task: {$backupTaskId}");

            $backupTask->setScriptUpdateTime();

            $logOutput .= $this->logWithTimestamp('Cleaned up the temporary zip file on server.', $userTimezone);
            $logOutput .= $this->logWithTimestamp('Backup task has finished successfully!', $userTimezone);
            $backupTaskLog->setSuccessfulTime();
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

        } catch (Exception $exception) {
            $logOutput .= 'Error in backup process: ' . $exception->getMessage() . "\n";
            Log::error("Error in backup process for task {$backupTaskId}: " . $exception->getMessage(), ['exception' => $exception]);
        } finally {
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);
            $backupTaskLog->setFinishedTime();
            $this->updateBackupTaskStatus($backupTask, BackupTask::STATUS_READY);
            $backupTask->sendNotifications();
            $backupTask->updateLastRanAt();
            $backupTask->resetScriptUpdateTime();
            Log::info('[BACKUP TASK] Completed backup task: ' . $backupTask->label . ' (' . $backupTask->id . ').');
        }
    }
}
