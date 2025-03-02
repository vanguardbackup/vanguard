<?php

declare(strict_types=1);

namespace App\Services\Backup\Tasks;

use App\Exceptions\BackupTaskZipException;
use App\Exceptions\SFTPConnectionException;
use App\Services\Backup\BackupConstants;
use League\Flysystem\FilesystemException;
use Override;
use RuntimeException;

/**
 * FileBackupTask
 *
 * This class extends AbstractBackupTask to provide specific implementation
 * for backing up files and directories. It handles the process of connecting to a remote server,
 * compressing the specified directory, and storing the backup in a specified destination.
 */
class FileBackupTask extends AbstractBackupTask
{
    /**
     * Perform the file backup.
     *
     * This method executes the following steps:
     * 1. Establishes an SFTP connection to the remote server.
     * 2. Checks if the specified source path exists.
     * 3. Calculates the size of the directory to be backed up.
     * 4. Detects if the source is a Laravel project and optimizes accordingly.
     * 5. Compresses the remote directory into a zip file.
     * 6. Uploads the zip file to the specified backup destination.
     * 7. Optionally rotates old backups based on configuration.
     * 8. Cleans up temporary files.
     *
     * @throws BackupTaskZipException If there's an error during the zip compression process
     * @throws SFTPConnectionException If there's an error establishing the SFTP connection
     * @throws RuntimeException|FilesystemException If there's a failure in the backup process, if the path doesn't exist, or if the directory size exceeds the limit
     */
    #[Override]
    protected function performBackup(): void
    {
        $this->backupTask->getAttribute('remoteServer');
        $backupDestinationModel = $this->backupTask->getAttribute('backupDestination');
        $sourcePath = $this->backupTask->getAttributeValue('source_path');
        $storagePath = $this->backupTask->getAttributeValue('store_path');

        $this->logMessage('Attempting to connect to remote server.');
        $sftp = $this->establishSFTPConnection($this->backupTask);
        $this->logMessage('Secure SSH connection established with the remote server.');

        if ($this->backupTask->hasPrescript()) {
            $preBackup = $this->performPreBackupScript($sftp);

            if ($preBackup) {
                $this->logMessage('Pre-backup script found for this backup task.');
            }
        }

        if (! $this->checkPathExists($sftp, $sourcePath)) {
            throw new RuntimeException('The path specified does not exist.');
        }

        $this->backupTask->setScriptUpdateTime();

        $excludeDirs = $this->getExcludedDirectories($sftp, $sourcePath);

        $isLaravel = $this->isLaravelDirectory($sftp, $sourcePath);

        if ($isLaravel) {
            $this->logMessage('Laravel project detected. Standard Laravel folder exclusions will be applied.');
        }

        $dirSize = $this->getRemoteDirectorySize($sftp, $sourcePath);
        $this->backupSize = $dirSize;
        $dirSizeInMB = number_format($dirSize / 1024 / 1024, 1);
        $this->logMessage(sprintf("Source directory '%s' size: %s MB.", $sourcePath, $dirSizeInMB));

        if ($dirSize > BackupConstants::FILE_SIZE_LIMIT) {
            throw new RuntimeException('Directory size exceeds the limit.');
        }

        $zipFileName = $this->generateBackupFileName('zip');
        $remoteZipPath = '/tmp/' . $zipFileName;

        $this->zipRemoteDirectory($sftp, $sourcePath, $remoteZipPath, $excludeDirs);
        $this->logMessage(sprintf('Directory compression complete. Archive location: %s.', $remoteZipPath));

        if ($this->backupTask->hasEncryptionPassword()) {
            $this->setFileEncryption($sftp, $remoteZipPath);
        }

        $this->backupTask->setScriptUpdateTime();

        if (! $this->backupDestinationDriver($backupDestinationModel->type, $sftp, $remoteZipPath, $backupDestinationModel, $zipFileName, $storagePath)) {
            throw new RuntimeException('Failed to upload the zip file to destination.');
        }

        // NOTE: Vanguard doesn't support backup rotations on the local driver due to how the abstract class methods are set up.
        // It is so heavily coupled to assuming it's a third party driver that it will be a nuisance to sort.
        // It will need to be addressed.
        if ($this->backupTask->isRotatingBackups() && ! $backupDestinationModel->isLocalConnection()) {
            $backupDestination = $this->createBackupDestinationInstance($backupDestinationModel);
            $this->rotateOldBackups($backupDestination, $this->backupTask->getAttribute('id'), $this->backupTask->getAttribute('maximum_backups_to_keep'), '.zip', 'backup_');
            $this->logMessage(sprintf('Initiating backup rotation. Retention limit: %s backups.', $this->backupTask->getAttribute('maximum_backups_to_keep')));
        }

        $this->logMessage(sprintf('File backup has been uploaded to %s - %s: %s', $backupDestinationModel->label, $backupDestinationModel->type(), $zipFileName));

        $sftp->delete($remoteZipPath);
        $this->logMessage('Temporary server file removed after successful backup operation.');

        if ($this->backupTask->hasPostScript()) {
            $postBackup = $this->performPostBackupScript($sftp);

            if ($postBackup) {
                $this->logMessage('Post-backup script found for this backup task.');
            }
        }
    }
}
