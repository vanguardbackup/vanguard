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
use App\Services\Backup\Destinations\Local;
use App\Services\Backup\Destinations\S3;
use App\Services\Backup\Traits\BackupHelpers;
use Closure;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use League\Flysystem\FilesystemException;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use RuntimeException;

/**
 * Abstract Backup Class
 *
 * This abstract class provides the core functionality for backup operations.
 * It includes methods for handling different backup destinations, SFTP operations,
 * database operations, file operations, and various utility functions.
 */
abstract class Backup
{
    use BackupHelpers;

    /**
     * Standard Laravel directories to always exclude from backups
     *
     * @var array<string>
     */
    protected array $laravelExclusions = [
        'node_modules/*',      // NPM dependencies
        'vendor/*',            // Composer dependencies
        'storage/framework/*', // Laravel framework cache/sessions/views
        'storage/logs/*',      // Laravel logs
        '.git/*',              // Git repository
        'bootstrap/cache/*',   // Cached bootstrap files
        '.docker/*',           // Docker config files if present
        '.github/*',           // GitHub workflows/config if present
        '.idea/*',             // PhpStorm settings if present
        '.vscode/*',           // VSCode settings if present
    ];

    /**
     * @var callable|Closure
     */
    private $sftpFactory;

    /**
     * Constructor for the Backup class.
     *
     * Initializes the backup process, validates the configuration,
     * and sets up the SFTP factory.
     *
     * @param  callable|null  $sftpFactory  Optional factory for creating SFTP instances
     */
    public function __construct(?callable $sftpFactory = null)
    {
        $this->logInfo('Initializing Backup class.');
        $this->validateConfiguration();

        $this->sftpFactory = $sftpFactory ?? fn (string $host, int $port, int $timeout): SFTPInterface => new SFTPAdapter($host, $port, $timeout);
    }

    /**
     * Handle backup to different destination drivers.
     *
     * @param  string  $destinationDriver  The type of destination driver
     * @param  SFTPInterface  $sftp  SFTP interface for file operations
     * @param  string  $remotePath  Path to the remote file
     * @param  BackupDestination  $backupDestination  Backup destination model
     * @param  string  $fileName  Name of the file to be backed up
     * @param  string  $storagePath  Storage path for the backup
     * @return bool True if backup was successful, false otherwise
     *
     * @throws RuntimeException|FilesystemException If an unsupported destination driver is specified
     */
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
            case BackupConstants::DRIVER_DO_SPACES:
            case BackupConstants::DRIVER_CUSTOM_S3:
                $client = $backupDestination->getS3Client();
                $bucketName = $backupDestination->getAttribute('s3_bucket_name');

                return (new S3($client, $bucketName))->streamFiles($sftp, $remotePath, $fileName, $storagePath);
            case BackupConstants::DRIVER_LOCAL:
                return (new Local($sftp, $storagePath))->streamFiles($sftp, $remotePath, $fileName, $storagePath);
            default:
                throw new RuntimeException('Unsupported destination driver: ' . $destinationDriver);
        }
    }

    /**
     * Validate the backup configuration.
     *
     * @throws BackupTaskRuntimeException If the configuration is invalid
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

    /**
     * Retrieve a BackupTask model instance by ID.
     *
     * @param  int  $backupTaskId  The ID of the backup task
     * @return BackupTask The BackupTask model instance
     */
    public function obtainBackupTask(int $backupTaskId): BackupTask
    {
        $this->logInfo('Obtaining backup task.', ['backup_task_id' => $backupTaskId]);

        return BackupTask::findOrFail($backupTaskId);
    }

    /**
     * Create a new BackupTaskLog record.
     *
     * @param  int  $backupTaskId  The ID of the associated backup task
     * @param  string  $logOutput  The initial log output
     * @return BackupTaskLog The created BackupTaskLog instance
     */
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

    /**
     * Update the output of a BackupTaskLog.
     *
     * @param  BackupTaskLog  $backupTaskLog  The BackupTaskLog to update
     * @param  string  $logOutput  The new log output
     */
    public function updateBackupTaskLogOutput(BackupTaskLog $backupTaskLog, string $logOutput): void
    {
        $this->logInfo('Updating backup task log output.', ['log_id' => $backupTaskLog->getAttribute('id')]);

        try {
            $this->logDebug('Dispatching StreamBackupTaskLogEvent');
            StreamBackupTaskLogEvent::dispatch($backupTaskLog, $logOutput);
        } catch (Exception $exception) {
            $this->handleException($exception, 'Error dispatching StreamBackupTaskLogEvent');
        }

        $backupTaskLog->forceFill(['output' => $logOutput]);
        $backupTaskLog->save();
        $this->logDebug('Backup task log output updated.', ['log_id' => $backupTaskLog->getAttribute('id'), 'output' => $logOutput]);
    }

    /**
     * Update the status of a BackupTask.
     *
     * @param  BackupTask  $backupTask  The BackupTask to update
     * @param  string  $status  The new status
     */
    public function updateBackupTaskStatus(BackupTask $backupTask, string $status): void
    {
        $this->logInfo('Updating backup task status.', ['backup_task_id' => $backupTask->getAttribute('id'), 'status' => $status]);

        $backupTask->forceFill(['status' => $status]);
        $this->logDebug('Task status updated.', ['backup_task_id' => $backupTask->getAttribute('id'), 'status' => $status]);

        BackupTaskStatusChanged::dispatch($backupTask, $status);
    }

    /**
     * Send an email notification for a failed backup task.
     *
     * @param  BackupTask  $backupTask  The failed BackupTask
     * @param  string  $errorMessage  The error message
     */
    public function sendEmailNotificationOfTaskFailure(BackupTask $backupTask, string $errorMessage): void
    {
        $this->logInfo('Sending failure notification email.', ['backup_task_id' => $backupTask->getAttribute('id'), 'error' => $errorMessage]);

        try {
            Mail::to($backupTask->getAttribute('user'))
                ->queue(new BackupTaskFailed($backupTask->getAttribute('user'), $backupTask->getAttribute('label'), $errorMessage));
        } catch (Exception $exception) {
            $this->handleException($exception, 'Failed to send task failure notification email.');
        }
    }

    /**
     * Get the size of a remote directory.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface
     * @param  string  $path  The path to the directory
     * @return int The size of the directory in bytes
     *
     * @throws SFTPConnectionException If there's an error in connection or command execution
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

        return is_string($output) ? (int) trim($output) : 0;
    }

    /**
     * Get the size of a remote database in bytes.
     *
     * @param  SFTPInterface  $sftp  SFTP connection interface
     * @param  string  $databaseType  Type of the database (mysql or postgresql)
     * @param  string  $databaseName  Name of the database
     * @param  string  $password  Database password
     * @return int Size of the database in bytes
     *
     * @throws SFTPConnectionException If there's an error in connection or unsupported database type
     */
    public function getRemoteDatabaseSize(SFTPInterface $sftp, string $databaseType, string $databaseName, string $password): int
    {
        $this->logInfo('Getting remote database size.', ['database_type' => $databaseType, 'database' => $databaseName]);

        $this->validateSFTP($sftp);

        $sizeCommand = $this->buildSizeCommand($databaseType, $databaseName, $password);

        $output = $sftp->exec($sizeCommand);
        $this->logDebug('Database size command output.', ['command' => $sizeCommand, 'output' => $output]);

        if (! is_string($output) || (trim($output) === '' || trim($output) === '0')) {
            $this->logError('Failed to get the database size.');
            throw new SFTPConnectionException('Failed to retrieve database size.');
        }

        return $this->parseSizeOutput(trim($output));
    }

    /**
     * Check if a path exists on the remote server.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface
     * @param  string  $path  The path to check
     * @return bool True if the path exists, false otherwise
     *
     * @throws SFTPConnectionException If there's an error in the SFTP connection
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
     * Establish an SFTP connection for a backup task.
     *
     * @param  BackupTask  $backupTask  The backup task
     * @return SFTPInterface The established SFTP connection
     *
     * @throws SFTPConnectionException If unable to establish the connection
     */
    public function establishSFTPConnection(BackupTask $backupTask): SFTPInterface
    {
        $remoteServer = $backupTask->getAttribute('remoteServer');
        $this->logInfo('Establishing SFTP connection.', ['remote_server' => $remoteServer->ip_address]);

        /** @var PrivateKey $asymmetricKey */
        $asymmetricKey = PublicKeyLoader::load(get_ssh_private_key(), config('app.ssh.passphrase'));

        $sftp = $this->createSFTP($remoteServer->ip_address, (int) $remoteServer->port, 120);

        $loginSuccess = $sftp->login($remoteServer->username, $asymmetricKey);

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
     * @param  SFTPInterface  $sftp  The SFTP interface
     * @param  string  $sourcePath  The source path to zip
     * @param  string  $remoteZipPath  The path where the zip file will be created
     * @param  array<string>  $excludeDirs  Directories to exclude from the zip
     *
     * @throws BackupTaskZipException If there's an error during the zip process
     * @throws SFTPConnectionException If there's an error in the SFTP connection
     */
    public function zipRemoteDirectory(SFTPInterface $sftp, string $sourcePath, string $remoteZipPath, array $excludeDirs = []): void
    {
        $this->logInfo('Zipping remote directory.', ['source_path' => $sourcePath, 'remote_zip_path' => $remoteZipPath]);

        $this->validateSFTP($sftp);

        $isLaravel = $this->isLaravelDirectory($sftp, $sourcePath);

        if ($isLaravel) {
            foreach ($this->laravelExclusions as $laravelExclusion) {
                if (! in_array($laravelExclusion, $excludeDirs)) {
                    $excludeDirs[] = $laravelExclusion;
                }
            }

            $this->logDebug('Laravel project detected. Added standard Laravel exclusions.', ['exclusions' => $this->laravelExclusions]);
        }

        if ($excludeDirs !== []) {
            $this->logDebug('The following directories will be excluded from the backup:', ['excluded_dirs' => $excludeDirs]);
        }

        // Check disk space
        $dirSizeCommand = 'du -sb ' . escapeshellarg($sourcePath) . ' | cut -f1';
        $dirSizeOutput = $sftp->exec($dirSizeCommand);
        $dirSize = is_string($dirSizeOutput) ? trim($dirSizeOutput) : '';

        if (! is_numeric($dirSize)) {
            $this->logError('Failed to get directory size.', ['source_path' => $sourcePath, 'dir_size_output' => $dirSizeOutput]);
            throw new BackupTaskZipException('Failed to get directory size.');
        }

        $this->logInfo('Directory size calculated.', ['source_path' => $sourcePath, 'dir_size' => $dirSize]);

        $diskSpaceCommand = 'df -P ' . escapeshellarg(dirname($remoteZipPath)) . ' | tail -1 | awk \'{print $4}\'';
        $diskSpaceOutput = $sftp->exec($diskSpaceCommand);
        $availableSpace = is_string($diskSpaceOutput) ? (int) trim($diskSpaceOutput) * 1024 : 0; // Convert from KB to bytes

        if ($availableSpace === 0 || ! is_numeric($availableSpace)) {
            $this->logError('Failed to get available disk space.', ['remote_zip_path' => $remoteZipPath, 'disk_space_output' => $diskSpaceOutput]);
            throw new BackupTaskZipException('Failed to get available disk space.');
        }

        $this->logInfo('Available disk space calculated.', ['remote_zip_path' => $remoteZipPath, 'available_space' => $availableSpace]);

        if ($availableSpace < $dirSize) {
            $this->logError('Not enough disk space to create the zip file.', [
                'source_path' => $sourcePath,
                'remote_zip_path' => $remoteZipPath,
                'required_space' => $dirSize,
                'available_space' => $availableSpace,
            ]);
            throw new BackupTaskZipException('Not enough disk space to create the zip file.');
        }

        $excludeArgs = array_map(fn ($dir): string => '--exclude=' . escapeshellarg($dir), $excludeDirs);
        $excludeArgsString = implode(' ', $excludeArgs);

        $logFile = $remoteZipPath . '.log';

        $zipCommand = 'cd ' . escapeshellarg($sourcePath) .
            ' && zip -rX ' . escapeshellarg($remoteZipPath) .
            ' . ' . $excludeArgsString .
            ' 2>&1 | grep -v "adding: " | grep -v "deflated" > ' . escapeshellarg($logFile);

        $this->logDebug('Executing zip command with filtered output.', ['zip_command' => $zipCommand]);

        $result = $this->retryCommand(
            fn (): bool|string => $sftp->exec($zipCommand),
            BackupConstants::ZIP_RETRY_MAX_ATTEMPTS,
            BackupConstants::ZIP_RETRY_DELAY_SECONDS
        );

        $logOutput = $sftp->exec('cat ' . escapeshellarg($logFile) . ' 2>/dev/null || echo ""');
        $errorFound = is_string($logOutput) && (
            stripos($logOutput, 'error') !== false ||
            stripos($logOutput, 'failed') !== false
        );

        $sftp->exec('rm -f ' . escapeshellarg($logFile) . ' 2>/dev/null');

        if ($result === false || $errorFound) {
            $error = $result === false ? $sftp->getLastError() : $logOutput;
            $this->logError('Failed to execute zip command after retries.', [
                'source_path' => $sourcePath,
                'remote_zip_path' => $remoteZipPath,
                'error' => $error,
            ]);
            throw new BackupTaskZipException('Failed to zip the directory after multiple attempts: ' . $error);
        }

        $checkFileCommand = 'test -f ' . escapeshellarg($remoteZipPath) . ' && stat -c%s ' . escapeshellarg($remoteZipPath);
        $fileCheckOutput = $sftp->exec($checkFileCommand);
        $this->logDebug('File check command output.', ['output' => $fileCheckOutput]);

        if (! is_string($fileCheckOutput)) {
            $this->logError('Failed to check zip file.', ['remote_zip_path' => $remoteZipPath, 'error' => 'Invalid output']);
            throw new BackupTaskZipException('Failed to check zip file: Invalid output');
        }

        $fileSize = trim($fileCheckOutput);
        if (! is_numeric($fileSize)) {
            $this->logError('Zip file does not exist or is empty after zipping.', ['remote_zip_path' => $remoteZipPath, 'file_size' => $fileSize]);
            throw new BackupTaskZipException('Zip file does not exist or is empty after zipping.');
        }

        $fileSizeMB = number_format((int) $fileSize / 1024 / 1024, 2);

        $this->logInfo('Remote directory successfully zipped.', [
            'source_path' => $sourcePath,
            'remote_zip_path' => $remoteZipPath,
            'file_size' => $fileSize,
            'file_size_mb' => $fileSizeMB . ' MB',
        ]);
    }

    /**
     * Determine the database type on the remote server.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface
     * @return string The detected database type
     *
     * @throws DatabaseDumpException If no supported database is found
     * @throws SFTPConnectionException If there's an error in the SFTP connection
     */
    public function getDatabaseType(SFTPInterface $sftp): string
    {
        $this->logInfo('Determining database type.');

        $this->validateSFTP($sftp);

        $mysqlOutput = $sftp->exec('mysql --version 2>&1');
        if (is_string($mysqlOutput) && stripos($mysqlOutput, 'mysql') !== false && stripos($mysqlOutput, 'not found') === false) {
            $this->logInfo('Database type determined: MySQL.');

            return BackupConstants::DATABASE_TYPE_MYSQL;
        }

        $psqlOutput = $sftp->exec('psql --version 2>&1');
        if (is_string($psqlOutput) && stripos($psqlOutput, 'psql') !== false && stripos($psqlOutput, 'not found') === false) {
            $this->logInfo('Database type determined: PostgreSQL.');

            return BackupConstants::DATABASE_TYPE_POSTGRESQL;
        }

        $this->logError('No supported database found on the remote server.');
        throw new DatabaseDumpException('No supported database found on the remote server.');
    }

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
                foreach ($tablesToExclude as $tableToExclude) {
                    $excludeTablesOption .= ' --ignore-table=' . escapeshellarg($databaseName . '.' . trim($tableToExclude));
                }
            } elseif ($databaseType === BackupConstants::DATABASE_TYPE_POSTGRESQL) {
                foreach ($tablesToExclude as $tableToExclude) {
                    $excludeTablesOption .= ' -T ' . escapeshellarg(trim($tableToExclude));
                }
            }

            Log::debug('Excluding tables from the database dump.', ['tables' => $tablesToExclude]);
        }

        $tempErrorLogPath = $remoteDumpPath . '.error.log';

        if ($databaseType === BackupConstants::DATABASE_TYPE_MYSQL) {
            $dumpCommand = sprintf(
                'mysqldump %s %s --password=%s > %s 2> %s',
                escapeshellarg($databaseName),
                $excludeTablesOption,
                escapeshellarg($databasePassword),
                escapeshellarg($remoteDumpPath),
                escapeshellarg($tempErrorLogPath)
            );
        } elseif ($databaseType === BackupConstants::DATABASE_TYPE_POSTGRESQL) {
            $dumpCommand = sprintf(
                'PGPASSWORD=%s pg_dump %s %s > %s 2> %s',
                escapeshellarg($databasePassword),
                escapeshellarg($databaseName),
                $excludeTablesOption,
                escapeshellarg($remoteDumpPath),
                escapeshellarg($tempErrorLogPath)
            );
        } else {
            $this->logError('Unsupported database type.', ['database_type' => $databaseType]);
            throw new DatabaseDumpException('Unsupported database type.');
        }

        $this->logDebug('Database dump command.', ['command' => $dumpCommand]);

        $output = $sftp->exec($dumpCommand);
        $this->logDebug('Database dump command output.', ['output' => $output]);

        $errorOutput = $sftp->exec('cat ' . escapeshellarg($tempErrorLogPath));
        if (is_string($errorOutput) && ! in_array(trim($errorOutput), ['', '0'], true)) {
            $this->logError('Error during database dump.', ['error' => $errorOutput]);
            $sftp->exec('rm ' . escapeshellarg($tempErrorLogPath));
            throw new DatabaseDumpException('Error during database dump: ' . $errorOutput);
        }

        $sftp->exec('rm ' . escapeshellarg($tempErrorLogPath));

        $fileSizeCommand = sprintf('stat -c %%s %s || echo "0"', escapeshellarg($remoteDumpPath));
        $fileSizeOutput = $sftp->exec($fileSizeCommand);
        $fileSize = is_string($fileSizeOutput) ? (int) trim($fileSizeOutput) : 0;

        if ($fileSize === 0) {
            $this->logError('Database dump file was not created or is empty.');
            throw new DatabaseDumpException('Database dump file was not created or is empty.');
        }

        $this->logInfo('Database dump completed successfully.', [
            'remote_dump_path' => $remoteDumpPath,
            'file_size' => $fileSize . ' bytes',
        ]);
    }

    /**
     * Validate the SFTP connection.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface to validate
     *
     * @throws SFTPConnectionException If the SFTP connection is not active
     */
    public function validateSFTP(SFTPInterface $sftp): void
    {
        if (! $sftp->isConnected()) {
            $this->logError('SFTP connection lost.');
            throw new SFTPConnectionException('SFTP connection lost.');
        }
    }

    /**
     * Retry a command multiple times.
     *
     * @param  callable  $command  The command to retry
     * @param  int  $maxRetries  Maximum number of retries
     * @param  int  $retryDelay  Delay between retries in seconds
     * @return mixed The result of the command if successful, false otherwise
     */
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

    /**
     * Check if a directory is a Laravel project.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface
     * @param  string  $sourcePath  The path to check
     * @return bool True if it's a Laravel project, false otherwise
     */
    public function isLaravelDirectory(SFTPInterface $sftp, string $sourcePath): bool
    {
        $this->logInfo('Checking if the directory is a Laravel project.', ['source_path' => $sourcePath]);

        $artisanExists = $sftp->stat($sourcePath . '/artisan') !== false;
        $composerJsonExists = $sftp->stat($sourcePath . '/composer.json') !== false;
        $packageJsonExists = $sftp->stat($sourcePath . '/package.json') !== false;

        $isLaravel = $artisanExists && $composerJsonExists && $packageJsonExists;
        $this->logDebug('Laravel directory check.', [
            'artisan_exists' => $artisanExists,
            'composer_json_exists' => $composerJsonExists,
            'package_json_exists' => $packageJsonExists,
            'is_laravel' => $isLaravel,
        ]);

        return $isLaravel;
    }

    /**
     * Delete a folder on the remote server.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface
     * @param  string  $folderPath  The path of the folder to delete
     */
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

    /**
     * Create an instance of BackupDestinationInterface based on the backup destination type.
     *
     * @param  BackupDestination  $backupDestination  The backup destination model
     * @return BackupDestinationInterface The created backup destination instance
     *
     * @throws RuntimeException If the backup destination type is unsupported
     */
    public function createBackupDestinationInstance(BackupDestination $backupDestination): BackupDestinationInterface
    {
        switch ($backupDestination->getAttribute('type')) {
            case BackupConstants::DRIVER_CUSTOM_S3:
            case BackupConstants::DRIVER_DO_SPACES:
            case BackupConstants::DRIVER_S3:
                $client = $backupDestination->getS3Client();

                return new S3($client, $backupDestination->getAttribute('s3_bucket_name'));
            default:
                throw new RuntimeException('Unsupported backup destination type: ' . $backupDestination->getAttribute('type'));
        }
    }

    /**
     * Get the list of directories to exclude from the backup
     *
     * @param  SFTPInterface  $sftp  The SFTP interface
     * @param  string  $sourcePath  The source path to check
     * @return array<string> List of directories to exclude
     */
    public function getExcludedDirectories(SFTPInterface $sftp, string $sourcePath): array
    {
        $excludeDirs = [];

        $isLaravel = $this->isLaravelDirectory($sftp, $sourcePath);

        if ($isLaravel) {
            $this->logInfo('Laravel project detected. Applying Laravel-specific exclusions.');
            $excludeDirs = $this->laravelExclusions;

            $findSymlinksCommand = 'find ' . escapeshellarg($sourcePath) . ' -type l -printf "%P\n"';
            $symlinksOutput = $sftp->exec($findSymlinksCommand);

            if (is_string($symlinksOutput) && ($symlinksOutput !== '' && $symlinksOutput !== '0')) {
                $symlinks = array_filter(explode("\n", trim($symlinksOutput)));

                foreach ($symlinks as $symlink) {
                    $excludeDirs[] = $symlink;
                    $this->logDebug('Excluding symlink from backup.', ['symlink' => $symlink]);
                }
            }
        }

        return $excludeDirs;
    }

    /**
     * Create an SFTP interface instance.
     *
     * @param  string  $host  The host to connect to
     * @param  int  $port  The port to connect to
     * @param  int  $timeout  The connection timeout in seconds
     * @return SFTPInterface The created SFTP interface instance
     */
    protected function createSFTP(string $host, int $port, int $timeout = 120): SFTPInterface
    {
        return ($this->sftpFactory)($host, $port, $timeout);
    }

    /**
     * Download a file via SFTP.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface
     * @param  string  $remoteZipPath  The path of the remote file to download
     * @return string The path to the downloaded temporary file
     *
     * @throws Exception If the download fails
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
     *  It opens the file content as a stream.
     *
     * @param  string  $tempFile  The temporary file.
     *
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

    /**
     *  It cleans up the temporary file that is created during a backup.
     *
     * @param  string  $tempFile  The temporary file itself
     */
    protected function cleanUpTempFile(string $tempFile): void
    {
        if (file_exists($tempFile)) {
            unlink($tempFile);
            $this->logDebug('Temporary file deleted.', ['temp_file' => $tempFile]);
        }
    }

    /**
     *  It creates a log message for the user on the front-end, informing them of the current step.
     *
     * @param  string  $message  The message itself
     * @param  string  $timezone  The timezone it should be set in
     * @return string The message formatted
     *
     * @throws Exception
     */
    protected function logWithTimestamp(string $message, string $timezone): string
    {
        $dt = new DateTime('now', new DateTimeZone($timezone));
        $timestamp = $dt->format('d-m-Y H:i:s');
        $this->logInfo('Log with timestamp.', ['timestamp' => $timestamp, 'message' => $message]);

        return '[' . $timestamp . '] ' . $message . "\n";
    }

    /**
     *  Handles the rotation of backup files depending on the specific configuration setting.
     *
     * @param  BackupDestinationInterface  $backupDestination  The backup destination
     * @param  int  $backupTaskId  The id of the backup task
     * @param  int  $backupLimit  The rotation limit
     * @param  string  $fileExtension  The file extension, it could be a zip or database file
     * @param  string  $pattern  The beginning string of the file name
     */
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
            $files = $backupDestination->listFiles(sprintf('%s%d_*%s', $pattern, $backupTaskId, $fileExtension));

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
        } catch (Exception $exception) {
            $this->logError('Error rotating old backups.', ['error' => $exception->getMessage()]);
            // Consider re-throwing the exception or handling it according to your error management strategy
        }
    }

    /**
     *  Logs the exception as an error in the Laravel log file.
     *
     * @param  Exception  $exception  The exception message
     * @param  string  $context  The context of the exception
     */
    protected function handleException(Exception $exception, string $context): void
    {
        $this->logError($context . ': ' . $exception->getMessage(), ['exception' => $exception]);
    }

    /**
     * Build the appropriate size command based on database type.
     *
     * @param  string  $databaseType  Type of the database
     * @param  string  $databaseName  Name of the database
     * @param  string  $password  Database password
     * @return string The command to execute
     *
     * @throws SFTPConnectionException If the database type is unsupported
     */
    private function buildSizeCommand(string $databaseType, string $databaseName, string $password): string
    {
        return match ($databaseType) {
            BackupConstants::DATABASE_TYPE_MYSQL => sprintf(
                "mysql -p%s -e \"SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = '%s';\"",
                escapeshellarg($password),
                escapeshellarg($databaseName)
            ),
            BackupConstants::DATABASE_TYPE_POSTGRESQL => sprintf(
                "PGPASSWORD=%s psql -d %s -c \"SELECT pg_database_size('%s');\" -t",
                escapeshellarg($password),
                escapeshellarg($databaseName),
                escapeshellarg($databaseName)
            ),
            default => throw new SFTPConnectionException('Unsupported database type: ' . $databaseType),
        };
    }

    /**
     * Parse the size output based on database type.
     *
     * @param  string  $output  Command output
     * @return int Size in bytes
     *
     * @throws SFTPConnectionException If parsing fails
     */
    private function parseSizeOutput(string $output): int
    {
        $size = filter_var($output, FILTER_SANITIZE_NUMBER_INT);

        if ($size === false || $size === null) {
            $this->logError('Failed to parse database size output.', ['output' => $output]);
            throw new SFTPConnectionException('Failed to parse database size output.');
        }

        return (int) $size;
    }
}
