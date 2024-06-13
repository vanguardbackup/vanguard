<?php

namespace App\Services\Backup\Tasks;

use App\Models\BackupTask;
use App\Services\Backup\Backup;
use Exception;
use Illuminate\Support\Facades\Log;

class DatabaseBackup extends Backup
{
    public function handle(int $backupTaskId): void
    {
        Log::info('Starting database backup task.', ['backup_task_id' => $backupTaskId]);

        $backupTask = $this->obtainBackupTask($backupTaskId);
        $backupTask->setScriptUpdateTime();
        $remoteServer = $backupTask->remoteServer;
        $backupDestinationModel = $backupTask->backupDestination;
        $databaseName = $backupTask->database_name;
        $databasePassword = $remoteServer->getDecryptedDatabasePassword();
        $userTimezone = $backupTask->user->timezone;
        $storagePath = $backupTask->getAttributeValue('store_path');

        $logOutput = '';
        $backupTaskLog = $this->recordBackupTaskLog($backupTaskId, $logOutput);
        $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

        $logOutput .= $this->logWithTimestamp('Backup task started.', $userTimezone);
        $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

        if (! $remoteServer->hasDatabasePassword()) {
            $logOutput .= $this->logWithTimestamp('Backup task failed.', $userTimezone);
            $errorMessage = $this->logWithTimestamp('Please provide a database password for the remote server.', $userTimezone);
            $logOutput .= $errorMessage;
            Log::error('Database password not provided for remote server.', ['backup_task_id' => $backupTaskId]);
            $this->handleFailure($backupTask, $logOutput, $errorMessage);

            return;
        }

        $this->updateBackupTaskStatus($backupTask, BackupTask::STATUS_RUNNING);
        $backupTask->setScriptUpdateTime();

        try {
            Log::info('Establishing SFTP connection.', ['remote_server' => $remoteServer->ip_address]);
            $sftp = $this->establishSFTPConnection($remoteServer);
            $logOutput .= $this->logWithTimestamp('SSH Connection established to the server.', $userTimezone);
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

            Log::info('Determining database type.', ['backup_task_id' => $backupTaskId]);
            $databaseType = $this->getDatabaseType($sftp);
            $logOutput .= $this->logWithTimestamp("Database type detected: {$databaseType}.", $userTimezone);
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

            $backupTask->setScriptUpdateTime();

            if ($backupTask->hasFileNameAppended()) {
                $dumpFileName = $backupTask->appended_file_name.'_backup_'.$backupTaskId.'_'.date('YmdHis').'.sql';
            } else {
                $dumpFileName = 'backup_'.$backupTaskId.'_'.date('YmdHis').'.sql';
            }
            $remoteDumpPath = "/tmp/{$dumpFileName}";
            Log::info('Dumping remote database.', ['backup_task_id' => $backupTaskId, 'dump_file_name' => $dumpFileName]);
            $this->dumpRemoteDatabase($sftp, $databaseType, $remoteDumpPath, $databasePassword, $databaseName, $backupTask->excluded_database_tables);
            Log::info('Database dump completed.', ['backup_task_id' => $backupTaskId, 'remote_dump_path' => $remoteDumpPath]);

            Log::info('Streaming database dump to destination.', ['backup_task_id' => $backupTaskId, 'remote_dump_path' => $remoteDumpPath, 'file_name' => $dumpFileName]);
            if (! $this->backupDestinationDriver($backupDestinationModel->type, $sftp, $remoteDumpPath, $backupDestinationModel, $dumpFileName, $storagePath)) {
                $errorMessage = $this->logWithTimestamp('Failed to upload the dump file to destination.', $userTimezone);
                Log::error('Failed to upload the dump file to destination.', ['backup_task_id' => $backupTaskId, 'remote_dump_path' => $remoteDumpPath]);
                $this->handleFailure($backupTask, $logOutput, $errorMessage);

                return;
            }
            Log::info('Database dump successfully uploaded to destination.', ['backup_task_id' => $backupTaskId, 'file_name' => $dumpFileName]);

            $backupTask->setScriptUpdateTime();

            if ($backupTask->isRotatingBackups()) {
                Log::info('Rotating old backups.', ['backup_task_id' => $backupTaskId]);
                $backupDestination = $this->createBackupDestinationInstance($backupDestinationModel);
                $this->rotateOldBackups($backupDestination, $backupTaskId, $backupTask->maximum_backups_to_keep, '.sql', 'backup_');
            }

            $logOutput .= $this->logWithTimestamp("Database backup has been uploaded to {$backupDestinationModel->label} - {$backupDestinationModel->type()}: {$dumpFileName}", $userTimezone);
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

            Log::info('Cleaning up remote dump file.', ['remote_dump_path' => $remoteDumpPath]);
            $sftp->delete($remoteDumpPath);
            Log::info('Remote dump file deleted.', ['remote_dump_path' => $remoteDumpPath]);

            $logOutput .= $this->logWithTimestamp('Cleaned up the temporary file on the server.', $userTimezone);
            $logOutput .= $this->logWithTimestamp('Backup task completed successfully!', $userTimezone);
            $backupTaskLog->setSuccessfulTime();
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

        } catch (Exception $exception) {
            $logOutput .= 'Error in backup process: '.$exception->getMessage()."\n";
            Log::error('Error in backup process.', ['backup_task_id' => $backupTaskId, 'error' => $exception->getMessage(), 'exception' => $exception]);
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);
        } finally {
            $this->updateBackupTaskLogOutput($backupTaskLog, $logOutput);
            $backupTaskLog->setFinishedTime();
            $this->updateBackupTaskStatus($backupTask, BackupTask::STATUS_READY);
            $backupTask->sendNotifications();
            $backupTask->updateLastRanAt();
            $backupTask->resetScriptUpdateTime();
            Log::info('Backup task completed.', ['backup_task_id' => $backupTaskId]);
        }
    }
}
