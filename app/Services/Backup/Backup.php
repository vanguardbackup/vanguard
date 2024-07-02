<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Events\BackupTaskStatusChanged;
use App\Events\CreatedBackupTaskLog;
use App\Events\StreamBackupTaskLogEvent;
use App\Exceptions\BackupTaskRuntimeException;
use App\Exceptions\BackupTaskZipException;
use App\Exceptions\DatabaseDumpException;
use App\Exceptions\SFTPConnectionException;
use App\Mail\BackupTaskFailed;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Services\Backup\Adapters\SFTPAdapter;
use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Destinations\Contracts\BackupDestinationInterface;
use App\Services\Backup\Destinations\S3;
use App\Services\Backup\Traits\BackupHelpers;
use Closure;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use RuntimeException;

abstract class Backup
{
    use BackupHelpers;

    /**
     * @var callable|Closure
     */
    private $sftpFactory;

    public function __construct(?callable $sftpFactory = null)
    {
        $this->logInfo('Initializing Backup class.');
        $this->validateConfiguration();

        $this->sftpFactory = $sftpFactory ?? function (string $host, int $port, int $timeout): SFTPInterface {
            return new SFTPAdapter($host, $port, $timeout);
        };
    }

    public function backupDestinationDriver(
        string $destinationDriver,
        SFTPInterface $sftp,
        string $remotePath,
        BackupDestination $backupDestination,
        string $fileName,
        string $storagePath
    ): bool {
        switch ($destinationDriver) {
            case BackupConstants::DRIVER_S3:
            case BackupConstants::DRIVER_CUSTOM_S3:
                $client = $backupDestination->getS3Client();
                $bucketName = $backupDestination->s3_bucket_name;

                return (new S3($client, $bucketName))->streamFiles($sftp, $remotePath, $fileName, $storagePath);

            default:
                throw new RuntimeException("Unsupported destination driver: {$destinationDriver}");
        }
    }

    /**
     * @throws BackupTaskRuntimeException
     */
    public function validateConfiguration(): void
    {
        $this->logInfo('Validating configuration.');
        if (! config('app.ssh.passphrase')) {
            $this->logCritical('The SSH passphrase is not set in the configuration.');
            throw new BackupTaskRuntimeException('The SSH passphrase is not set in the configuration.');
        }

        if (! ssh_keys_exist() && config('app.env') === 'production') {
            $this->logCritical('Unable to locate SSH keys required for the backup process.');
            throw new BackupTaskRuntimeException('Unable to locate SSH keys required for the backup process.');
        }

        $this->logInfo('Configuration validation passed.');
    }

    public function obtainBackupTask(int $backupTaskId): BackupTask
    {
        $this->logInfo('Obtaining backup task.', ['backup_task_id' => $backupTaskId]);

        return BackupTask::findOrFail($backupTaskId);
    }

    public function recordBackupTaskLog(int $backupTaskId, string $logOutput): BackupTaskLog
    {
        $this->logInfo('Recording backup task log.', ['backup_task_id' => $backupTaskId]);
        $backupTaskLog = BackupTaskLog::create([
            'backup_task_id' => $backupTaskId,
            'output' => $logOutput,
        ]);

        $this->logDebug('Backup task log created.', ['log_id' => $backupTaskLog->id]);

        CreatedBackupTaskLog::dispatch($backupTaskLog);

        return $backupTaskLog;
    }

    public function updateBackupTaskLogOutput(BackupTaskLog $backupTaskLog, string $logOutput): void
    {
        $this->logInfo('Updating backup task log output.', ['log_id' => $backupTaskLog->id]);

        try {
            $this->logDebug('Dispatching StreamBackupTaskLogEvent');
            StreamBackupTaskLogEvent::dispatch($backupTaskLog, $logOutput);
        } catch (Exception $e) {
            $this->handleException($e, 'Error dispatching StreamBackupTaskLogEvent');
        }

        $backupTaskLog->forceFill(['output' => $logOutput]);
        $backupTaskLog->save();
        $this->logDebug('Backup task log output updated.', ['log_id' => $backupTaskLog->id, 'output' => $logOutput]);
    }

    public function updateBackupTaskStatus(BackupTask $backupTask, string $status): void
    {
        $this->logInfo('Updating backup task status.', ['backup_task_id' => $backupTask->id, 'status' => $status]);

        $backupTask->forceFill(['status' => $status]);
        $this->logDebug('Task status updated.', ['backup_task_id' => $backupTask->id, 'status' => $status]);

        BackupTaskStatusChanged::dispatch($backupTask, $status);
    }

    public function sendEmailNotificationOfTaskFailure(BackupTask $backupTask, string $errorMessage): void
    {
        $this->logInfo('Sending failure notification email.', ['backup_task_id' => $backupTask->id, 'error' => $errorMessage]);

        try {
            Mail::to($backupTask->user)
                ->queue(new BackupTaskFailed($backupTask->user, $backupTask->label, $errorMessage));
        } catch (Exception $e) {
            $this->handleException($e, 'Failed to send task failure notification email.');
        }
    }

    public function handleFailure(BackupTask $backupTask, string &$logOutput, string $errorMessage): void
    {
        $this->logError('Handling failure for backup task.', ['backup_task_id' => $backupTask->id, 'error' => $errorMessage]);

        $logOutput .= "\n" . $errorMessage;
        $this->sendEmailNotificationOfTaskFailure($backupTask, $errorMessage);
    }

    /**
     * @throws SFTPConnectionException
     */
    public function getRemoteDirectorySize(SFTPInterface $sftp, string $path): int
    {
        $this->logInfo('Getting remote directory size.', ['path' => $path]);

        $this->validateSFTP($sftp);

        $testCommand = 'du --version';
        $testOutput = $sftp->exec($testCommand);
        if (! $testOutput) {
            $this->logError('The du command is not available on the remote server.');
            throw new SFTPConnectionException('The du command is not available on the remote server.');
        }

        $sizeCommand = 'du -sb ' . escapeshellarg($path) . ' | cut -f1';
        $output = $sftp->exec($sizeCommand);
        $this->logDebug('Directory size command output.', ['output' => $output]);

        return (int) trim($output);
    }

    /**
     * @throws SFTPConnectionException
     */
    public function checkPathExists(SFTPInterface $sftp, string $path): bool
    {
        $this->logInfo('Checking if path exists.', ['path' => $path]);

        $this->validateSFTP($sftp);
        $result = $sftp->stat($path);

        $exists = $result !== false;
        $this->logDebug('Path existence check result.', ['path' => $path, 'exists' => $exists]);

        return $exists;
    }

    /**
     * @throws SFTPConnectionException
     */
    public function establishSFTPConnection(object $remoteServer, object $backupTask): SFTPInterface
    {
        $this->logInfo('Establishing SFTP connection.', ['remote_server' => $remoteServer->ip_address]);

        /** @var PrivateKey $key */
        $key = PublicKeyLoader::load(get_ssh_private_key(), config('app.ssh.passphrase'));

        $sftp = $this->createSFTP($remoteServer->ip_address, (int) $remoteServer->port, 120);

        if ($backupTask->hasIsolatedCredentials()) {
            $loginSuccess = $sftp->login($backupTask->isolated_username, $key); // We're passing the isolated username + our SSH key here. Password is used for sudo.
        } else {
            $loginSuccess = $sftp->login($remoteServer->username, $key);
        }

        if (! $loginSuccess) {
            $error = $sftp->getLastError();
            $this->logError('SSH login failed.', ['error' => $error]);
            throw new SFTPConnectionException('SSH Login failed: ' . $error);
        }

        $remoteServer->markAsOnlineIfStatusIsNotOnline();
        $this->logInfo('SFTP connection established.', ['remote_server' => $remoteServer->ip_address]);

        return $sftp;
    }

    /**
     * Zip a remote directory, excluding specified directories.
     *
     * @param  array<string>  $excludeDirs
     *
     * @throws BackupTaskZipException
     * @throws SFTPConnectionException
     */
    public function zipRemoteDirectory(SFTPInterface $sftp, string $sourcePath, string $remoteZipPath, array $excludeDirs = []): void
    {
        $this->logInfo('Zipping remote directory.', ['source_path' => $sourcePath, 'remote_zip_path' => $remoteZipPath]);

        $this->validateSFTP($sftp);

        $dirSizeCommand = 'du -sb ' . escapeshellarg($sourcePath) . ' | cut -f1';
        $dirSizeOutput = $sftp->exec($dirSizeCommand);
        $dirSize = trim($dirSizeOutput);

        if (! is_numeric($dirSize)) {
            $this->logError('Failed to get directory size.', ['source_path' => $sourcePath, 'dir_size_output' => $dirSizeOutput]);
            throw new BackupTaskZipException('Failed to get directory size.');
        }

        if (! is_numeric($dirSize)) {
            $this->logError('Failed to get directory size.', ['source_path' => $sourcePath, 'dir_size_output' => $dirSizeOutput]);
            throw new BackupTaskZipException('Failed to get directory size.');
        }

        $this->logInfo('Directory size calculated.', ['source_path' => $sourcePath, 'dir_size' => $dirSize]);

        $diskSpaceCommand = 'df -P ' . escapeshellarg(dirname($remoteZipPath)) . ' | tail -1 | awk \'{print $4}\'';
        $diskSpaceOutput = $sftp->exec($diskSpaceCommand);
        $availableSpace = (int) trim($diskSpaceOutput) * 1024; // Convert from KB to bytes

        if ($availableSpace === 0 || ! is_numeric($availableSpace)) {
            $this->logError('Failed to get available disk space.', ['remote_zip_path' => $remoteZipPath, 'disk_space_output' => $diskSpaceOutput]);
            throw new BackupTaskZipException('Failed to get available disk space.');
        }

        $this->logInfo('Available disk space calculated.', ['remote_zip_path' => $remoteZipPath, 'available_space' => $availableSpace]);

        if ($availableSpace < $dirSize) {
            $this->logError('Not enough disk space to create the zip file.', ['source_path' => $sourcePath, 'remote_zip_path' => $remoteZipPath, 'required_space' => $dirSize, 'available_space' => $availableSpace]);
            throw new BackupTaskZipException('Not enough disk space to create the zip file.');
        }

        $excludeArgs = array_map(fn ($dir) => '--exclude=' . escapeshellarg($sourcePath . '/' . $dir), $excludeDirs);
        $excludeArgsString = implode(' ', $excludeArgs);

        $zipCommand = 'cd ' . escapeshellarg($sourcePath) . ' && zip -rv ' . escapeshellarg($remoteZipPath) . ' . ' . $excludeArgsString;
        $this->logDebug('Executing zip command.', ['zip_command' => $zipCommand]);

        $result = $this->retryCommand(function () use ($sftp, $zipCommand) {
            return $sftp->exec($zipCommand);
        }, BackupConstants::ZIP_RETRY_MAX_ATTEMPTS, BackupConstants::ZIP_RETRY_DELAY_SECONDS);

        if ($result === false) {
            $error = $sftp->getLastError();
            $this->logError('Failed to execute zip command after retries.', ['source_path' => $sourcePath, 'remote_zip_path' => $remoteZipPath, 'error' => $error]);
            throw new BackupTaskZipException('Failed to zip the directory after multiple attempts: ' . $error);
        }

        $checkFileCommand = 'test -f ' . escapeshellarg($remoteZipPath) . ' && stat -c%s ' . escapeshellarg($remoteZipPath);
        $fileCheckOutput = $sftp->exec($checkFileCommand);
        $this->logDebug('File check command output.', ['output' => $fileCheckOutput]);

        if ($fileCheckOutput === false) {
            $error = $sftp->getLastError();
            $this->logError('Failed to check zip file.', ['remote_zip_path' => $remoteZipPath, 'error' => $error]);
            throw new BackupTaskZipException('Failed to check zip file: ' . $error);
        }

        $fileSize = trim($fileCheckOutput);
        if (! is_numeric($fileSize) || $fileSize == 0) {
            $this->logError('Zip file does not exist or is empty after zipping.', ['remote_zip_path' => $remoteZipPath, 'file_size' => $fileSize]);
            throw new BackupTaskZipException('Zip file does not exist or is empty after zipping.');
        }

        $this->logInfo('Remote directory successfully zipped.', ['source_path' => $sourcePath, 'remote_zip_path' => $remoteZipPath, 'file_size' => $fileSize]);
    }

    /**
     * @throws DatabaseDumpException
     * @throws SFTPConnectionException
     */
    public function getDatabaseType(SFTPInterface $sftp): string
    {
        $this->logInfo('Determining database type.');

        $this->validateSFTP($sftp);

        $mysqlOutput = $sftp->exec('mysql --version 2>&1');
        if (stripos($mysqlOutput, 'mysql') !== false && stripos($mysqlOutput, 'not found') === false) {
            $this->logInfo('Database type determined: MySQL.');

            return BackupConstants::DATABASE_TYPE_MYSQL;
        }

        $psqlOutput = $sftp->exec('psql --version 2>&1');
        if (stripos($psqlOutput, 'psql') !== false && stripos($psqlOutput, 'not found') === false) {
            $this->logInfo('Database type determined: PostgreSQL.');

            return BackupConstants::DATABASE_TYPE_POSTGRESQL;
        }

        $this->logError('No supported database found on the remote server.');
        throw new DatabaseDumpException('No supported database found on the remote server.');
    }

    /**
     * @throws DatabaseDumpException
     * @throws SFTPConnectionException
     */
    public function dumpRemoteDatabase(
        SFTPInterface $sftp,
        string $databaseType,
        string $remoteDumpPath,
        string $databasePassword,
        string $databaseName,
        ?string $databaseTablesToExcludeInTheBackup
    ): void {
        $this->logInfo('Dumping remote database.', ['database_type' => $databaseType, 'remote_dump_path' => $remoteDumpPath]);

        $this->validateSFTP($sftp);

        $excludeTablesOption = '';
        if ($databaseTablesToExcludeInTheBackup) {
            $tablesToExclude = explode(',', $databaseTablesToExcludeInTheBackup);
            if ($databaseType === BackupConstants::DATABASE_TYPE_MYSQL) {
                foreach ($tablesToExclude as $table) {
                    $excludeTablesOption .= ' --ignore-table=' . escapeshellarg($databaseName . '.' . $table);
                }
            } elseif ($databaseType === BackupConstants::DATABASE_TYPE_POSTGRESQL) {
                foreach ($tablesToExclude as $table) {
                    $excludeTablesOption .= ' -T ' . escapeshellarg($table);
                }
            }
            Log::debug('Excluding tables from the database dump.', ['tables' => $tablesToExclude]);
        }

        if ($databaseType === BackupConstants::DATABASE_TYPE_MYSQL) {
            $dumpCommand = sprintf(
                'mysqldump %s %s --password=%s > %s 2>&1',
                escapeshellarg($databaseName),
                $excludeTablesOption,
                escapeshellarg($databasePassword),
                escapeshellarg($remoteDumpPath)
            );
        } elseif ($databaseType === BackupConstants::DATABASE_TYPE_POSTGRESQL) {
            $dumpCommand = sprintf(
                'PGPASSWORD=%s pg_dump %s %s > %s 2>&1',
                escapeshellarg($databasePassword),
                escapeshellarg($databaseName),
                $excludeTablesOption,
                escapeshellarg($remoteDumpPath)
            );
        } else {
            $this->logError('Unsupported database type.', ['database_type' => $databaseType]);
            throw new DatabaseDumpException('Unsupported database type.');
        }

        $this->logDebug('Database dump command.', ['command' => $dumpCommand]);

        $output = $sftp->exec($dumpCommand);
        $this->logDebug('Database dump command output.', ['output' => $output]);

        if (stripos($output, 'error') !== false || stripos($output, 'failed') !== false) {
            $this->logError('Failed to dump the database.', ['output' => $output]);
            throw new DatabaseDumpException('Failed to dump the database: ' . $output);
        }

        $checkFileCommand = sprintf('test -s %s && echo "exists" || echo "not exists"', escapeshellarg($remoteDumpPath));
        $fileCheckOutput = trim($sftp->exec($checkFileCommand));

        if ($fileCheckOutput !== 'exists') {
            $this->logError('Database dump file was not created or is empty.');
            throw new DatabaseDumpException('Database dump file was not created or is empty.');
        }

        $fileContent = $sftp->exec('cat ' . escapeshellarg($remoteDumpPath));
        $this->logDebug('Database dump file content snippet.', ['content' => substr($fileContent, 0, 500)]);

        $this->logInfo('Database dump completed successfully.', ['remote_dump_path' => $remoteDumpPath]);
    }

    /**
     * @throws SFTPConnectionException
     */
    public function validateSFTP(SFTPInterface $sftp): void
    {
        if (! $sftp->isConnected()) {
            $this->logError('SFTP connection lost.');
            throw new SFTPConnectionException('SFTP connection lost.');
        }
    }

    public function retryCommand(callable $command, int $maxRetries, int $retryDelay): mixed
    {
        $attempt = 0;
        $result = false;

        while ($attempt < $maxRetries) {
            $result = $command();
            if ($result !== false) {
                break;
            }

            $this->logWarning('Command failed, retrying...', ['attempt' => $attempt + 1, 'max_retries' => $maxRetries]);
            sleep($retryDelay);
            $attempt++;
        }

        return $result;
    }

    public function isLaravelDirectory(SFTPInterface $sftp, string $sourcePath): bool
    {
        $this->logInfo('Checking if the directory is a Laravel project.', ['source_path' => $sourcePath]);

        $artisanExists = $sftp->stat("{$sourcePath}/artisan") !== false;
        $composerJsonExists = $sftp->stat("{$sourcePath}/composer.json") !== false;
        $packageJsonExists = $sftp->stat("{$sourcePath}/package.json") !== false;

        $isLaravel = $artisanExists && $composerJsonExists && $packageJsonExists;
        $this->logDebug('Laravel directory check.', [
            'artisan_exists' => $artisanExists,
            'composer_json_exists' => $composerJsonExists,
            'package_json_exists' => $packageJsonExists,
            'is_laravel' => $isLaravel,
        ]);

        return $isLaravel;
    }

    public function deleteFolder(SFTPInterface $sftp, string $folderPath): void
    {
        $this->logInfo('Deleting folder.', ['folder_path' => $folderPath]);

        $deleteCommand = 'rm -rf ' . escapeshellarg($folderPath);
        $result = $sftp->exec($deleteCommand);

        if ($result === false) {
            $this->logError('Failed to delete folder.', ['folder_path' => $folderPath, 'error' => $sftp->getLastError()]);
        } else {
            $this->logInfo('Folder deleted.', ['folder_path' => $folderPath]);
        }
    }

    public function createBackupDestinationInstance(BackupDestination $backupDestinationModel): BackupDestinationInterface
    {
        switch ($backupDestinationModel->type) {
            case BackupConstants::DRIVER_CUSTOM_S3:
            case BackupConstants::DRIVER_S3:
                $client = $backupDestinationModel->getS3Client();

                return new S3($client, $backupDestinationModel->s3_bucket_name);

            default:
                throw new RuntimeException("Unsupported backup destination type: {$backupDestinationModel->type}");
        }
    }

    protected function createSFTP(string $host, int $port, int $timeout = 120): SFTPInterface
    {
        return ($this->sftpFactory)($host, $port, $timeout);
    }

    /**
     * @throws Exception
     */
    protected function downloadFileViaSFTP(SFTPInterface $sftp, string $remoteZipPath): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'sftp');
        if (! $sftp->get($remoteZipPath, $tempFile)) {
            $error = $sftp->getLastError();
            $this->logError('Failed to download the remote file.', ['remote_zip_path' => $remoteZipPath, 'error' => $error]);
            throw new Exception('Failed to download the remote file: ' . $error);
        }
        $this->logDebug('Remote file downloaded.', ['temp_file' => $tempFile]);

        return $tempFile;
    }

    /**
     * @throws Exception
     */
    protected function openFileAsStream(string $tempFile): mixed
    {
        $stream = fopen($tempFile, 'rb+');
        if (! $stream) {
            $error = error_get_last();
            $this->logError('Failed to open the temporary file as a stream.', ['temp_file' => $tempFile, 'error' => $error]);
            throw new Exception('Failed to open the temporary file as a stream: ' . json_encode($error));
        }
        $this->logDebug('Temporary file opened as a stream.');

        return $stream;
    }

    protected function cleanUpTempFile(string $tempFile): void
    {
        if (file_exists($tempFile)) {
            unlink($tempFile);
            $this->logDebug('Temporary file deleted.', ['temp_file' => $tempFile]);
        }
    }

    /**
     * @throws Exception
     */
    protected function logWithTimestamp(string $message, string $timezone): string
    {
        $dt = new DateTime('now', new DateTimeZone($timezone));
        $timestamp = $dt->format('d-m-Y H:i:s');
        $this->logInfo('Log with timestamp.', ['timestamp' => $timestamp, 'message' => $message]);

        return '[' . $timestamp . '] ' . $message . "\n";
    }

    protected function rotateOldBackups(
        BackupDestinationInterface $backupDestination,
        int $backupTaskId,
        int $backupLimit,
        string $fileExtension = '.zip',
        string $pattern = 'backup_'
    ): void {
        $this->logInfo('Rotating old backups.', ['backup_task_id' => $backupTaskId, 'backup_limit' => $backupLimit]);

        try {
            /** @var array<array<string, mixed>> $files */
            $files = $backupDestination->listFiles("{$pattern}{$backupTaskId}_*{$fileExtension}");

            $this->logDebug('Files filtered and sorted.', ['file_count' => count($files)]);

            while (count($files) > $backupLimit) {
                $oldestFile = array_pop($files);

                if (! is_array($oldestFile)) {
                    $this->logError('Invalid file structure encountered.', ['file' => $oldestFile]);

                    continue;
                }

                $file = $oldestFile['Key'] ?? $oldestFile['name'] ?? null;

                if (! is_string($file)) {
                    $this->logError('Invalid file name or key.', ['file' => $oldestFile]);

                    continue;
                }

                $this->logDebug('Deleting old backup.', ['file' => $file]);

                $backupDestination->deleteFile($file);
            }

            $this->logInfo('Old backups rotation completed.', ['remaining_files' => count($files)]);
        } catch (Exception $e) {
            $this->logError('Error rotating old backups.', ['error' => $e->getMessage()]);
            // Consider re-throwing the exception or handling it according to your error management strategy
        }
    }

    protected function handleException(Exception $e, string $context): void
    {
        $this->logError($context . ': ' . $e->getMessage(), ['exception' => $e]);
    }
}
