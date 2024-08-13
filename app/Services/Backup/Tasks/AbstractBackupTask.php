<?php

declare(strict_types=1);

namespace App\Services\Backup\Tasks;

use App\Exceptions\DatabaseDumpException;
use App\Exceptions\SFTPConnectionException;
use App\Models\BackupTask as BackupTaskModel;
use App\Models\BackupTaskLog;
use App\Models\User;
use App\Services\Backup\Backup;
use App\Services\Backup\Contracts\SFTPInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * AbstractBackupTask
 *
 * This abstract class provides a framework for implementing backup tasks.
 * It handles the core functionality of initializing, executing, and cleaning up backup operations.
 */
abstract class AbstractBackupTask extends Backup
{
    protected BackupTaskModel $backupTask;

    protected BackupTaskLog $backupTaskLog;

    protected string $logOutput = '';

    protected float $scriptRunTime;

    protected ?int $backupSize = null;

    /**
     * Constructor for AbstractBackupTask.
     *
     * @param  int  $backupTaskId  The ID of the backup task to be executed
     */
    public function __construct(int $backupTaskId)
    {
        parent::__construct();
        $this->scriptRunTime = microtime(true);
        $this->backupTask = $this->obtainBackupTask($backupTaskId);
    }

    /**
     * Main method to handle the backup process.
     *
     * This method orchestrates the entire backup process, including initialization,
     * execution, finalization, and cleanup.
     *
     * @throws Exception If an unexpected error occurs during the backup process
     */
    public function handle(): void
    {
        Log::info('Starting backup task: ' . $this->backupTask->id);

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

    /**
     * Generate a filename for the backup.
     *
     * @param  string  $extension  The file extension to be used
     * @return string The generated filename
     */
    public function generateBackupFileName(string $extension): string
    {
        $prefix = $this->backupTask->hasFileNameAppended() ? $this->backupTask->appended_file_name . '_' : '';

        return sprintf('%sbackup_%s_', $prefix, $this->backupTask->id) . Carbon::now()->format('YmdHis') . ('.' . $extension);
    }

    /**
     * Abstract method to perform the actual backup.
     * This method should be implemented by concrete backup task classes.
     */
    abstract protected function performBackup(): void;

    /**
     * Initialize the backup process.
     *
     * This method sets up the necessary logs and updates the backup task status.
     *
     * @throws Exception If an error occurs during initialization
     */
    protected function initializeBackup(): void
    {
        $this->backupTask->setScriptUpdateTime();
        $this->backupTaskLog = $this->recordBackupTaskLog($this->backupTask->id, $this->logOutput);
        $this->updateBackupTaskStatus($this->backupTask, BackupTaskModel::STATUS_RUNNING);
        $this->logMessage('Backup task initiated.');
        $this->updateBackupTaskLogOutput($this->backupTaskLog, $this->logOutput);
    }

    /**
     * Finalize a successful backup.
     *
     * This method updates logs and marks the backup as successful.
     *
     * @throws Exception If an error occurs during finalization
     */
    protected function finalizeSuccessfulBackup(): void
    {
        $this->logMessage('Backup task has been completed.');
        $this->backupTaskLog->setSuccessfulTime();
        $this->updateBackupTaskLogOutput($this->backupTaskLog, $this->logOutput);
    }

    /**
     * Handle backup failure.
     *
     * This method logs the error, sends notifications, and updates the backup status.
     *
     * @param  Throwable  $throwable  The exception that caused the backup failure
     */
    protected function handleBackupFailure(Throwable $throwable): void
    {
        $this->logOutput .= 'Error in backup process: ' . $throwable->getMessage() . "\n";
        $this->sendEmailNotificationOfTaskFailure($this->backupTask, $throwable->getMessage());
        Log::error(sprintf('Error in backup process for task %s: ', $this->backupTask->id) . $throwable->getMessage(), ['exception' => $throwable]);
    }

    /**
     * Clean up after the backup process.
     *
     * This method updates logs, resets statuses, sends notifications, and records backup metrics.
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

        $this->logMessage(sprintf('Backup summary: Operation completed in %s seconds.', $elapsedTime));

        Log::info(sprintf('Completed backup task: %s (%s).', $this->backupTask->label, $this->backupTask->id));
    }

    /**
     * Log a message with a timestamp.
     *
     * @param  string  $message  The message to log
     * @param  string  $timezone  The timezone to use for the timestamp
     * @return string The timestamped message
     *
     * @throws Exception If an error occurs during timestamp generation
     */
    protected function logWithTimestamp(string $message, string $timezone): string
    {
        $timestampedMessage = parent::logWithTimestamp($message, $timezone);
        $this->logOutput .= $timestampedMessage;

        return $timestampedMessage;
    }

    /**
     * Log a message using the user's timezone.
     *
     * @param  string  $message  The message to log
     *
     * @throws Exception If an error occurs during logging
     */
    protected function logMessage(string $message): void
    {
        /** @var User $user */
        $user = $this->backupTask->user;

        $this->logWithTimestamp($message, $user->getAttribute('timezone'));
    }

    /**
     * Encrypts the backup file on the remote server using the specified password.
     *
     * @param  SFTPInterface  $sftp  The SFTP connection
     * @param  string  $remoteFilePath  The path to the file to be encrypted
     *
     * @throws RuntimeException|Exception If encryption fails
     */
    protected function setFileEncryption(SFTPInterface $sftp, string $remoteFilePath): void
    {
        $this->ensureEncryptionPassword();
        $this->ensureOpensslCommandExists($sftp);

        $iv = $this->generateSecureIV();
        $encryptCommand = $this->buildEncryptCommand($remoteFilePath, $iv);

        $this->logMessage('Encrypting backup file.');
        $result = $sftp->exec($encryptCommand);

        if ($result === false) {
            $this->handleEncryptionFailure($sftp->getLastError() ?: 'Unknown error during encryption');
        }

        if (is_string($result) && stripos($result, 'error') !== false) {
            $this->handleEncryptionFailure($result);
        }

        $this->logMessage('Backup file encrypted successfully.');
    }

    /**
     * Ensures that the required 'openssl' command is available on the remote system.
     *
     * @param  SFTPInterface  $sftp  The SFTP connection to use for command execution
     *
     * @throws RuntimeException If the 'openssl' command is not available
     */
    private function ensureOpensslCommandExists(SFTPInterface $sftp): void
    {
        $result = $sftp->exec('command -v openssl');
        if ($result === false || ($result === '' || $result === '0')) {
            $this->logError('The openssl command is not available on the remote system.');
            throw new RuntimeException('Required openssl command not found on the remote system.');
        }
    }

    /**
     * Ensures that an encryption password is set for the backup task.
     *
     * @throws RuntimeException If no encryption password is set
     */
    private function ensureEncryptionPassword(): void
    {
        if (! $this->backupTask->hasEncryptionPassword()) {
            $this->logError('Attempted to set encryption for this backup but no encryption password was supplied.', ['backup_task' => $this->backupTask]);
            throw new RuntimeException('Encryption password is missing.');
        }
    }

    /**
     * Generates a cryptographically secure initialization vector (IV) for encryption.
     *
     * @return string A 16-byte string to be used as the IV
     *
     * @throws RuntimeException If a secure IV cannot be generated
     */
    private function generateSecureIV(): string
    {
        /** @var string|false $iv */
        $iv = openssl_random_pseudo_bytes(16, $strong);

        if ($iv === false || ! $strong) {
            $this->logError('Failed to generate a cryptographically strong IV.');
            throw new RuntimeException('Failed to generate a secure initialization vector.');
        }

        return $iv;
    }

    /**
     * Builds the OpenSSL command for encrypting the backup file.
     *
     * @param  string  $remoteFilePath  The path to the file to be encrypted on the remote server
     * @param  string  $iv  The initialization vector to use for encryption
     * @return string The complete OpenSSL command for file encryption
     */
    private function buildEncryptCommand(string $remoteFilePath, string $iv): string
    {
        $encryptionPassword = $this->backupTask->getAttribute('encryption_password');
        $ivHex = bin2hex($iv);

        return sprintf(
            'openssl enc -aes-256-cbc -in %s -out %s.enc -pass pass:%s -pbkdf2 -iter 100000 -nosalt && ' .
            'echo -n %s | xxd -r -p | cat - %s.enc > %s.tmp && ' .
            'mv %s.tmp %s && rm %s.enc',
            escapeshellarg($remoteFilePath),
            escapeshellarg($remoteFilePath),
            escapeshellarg((string) $encryptionPassword),
            $ivHex,
            escapeshellarg($remoteFilePath),
            escapeshellarg($remoteFilePath),
            escapeshellarg($remoteFilePath),
            escapeshellarg($remoteFilePath),
            escapeshellarg($remoteFilePath)
        );
    }

    /**
     * Handles encryption failure by logging the error and throwing an exception.
     *
     * @param  string  $error  The error message describing why encryption failed
     *
     * @throws RuntimeException Always thrown to indicate encryption failure
     */
    private function handleEncryptionFailure(string $error): void
    {
        $this->logError('Failed to encrypt the backup file.', ['error' => $error]);
        throw new RuntimeException("Failed to encrypt the backup file: {$error}");
    }
}
