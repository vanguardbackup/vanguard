<?php

declare(strict_types=1);

namespace App\Services\Backup\Tasks;

use App\Exceptions\BackupTaskZipException;
use App\Exceptions\SFTPConnectionException;
use App\Services\Backup\BackupConstants;
use RuntimeException;

class FileBackupTask extends AbstractBackupTask
{
    /**
     * @throws BackupTaskZipException
     * @throws SFTPConnectionException
     */
    protected function performBackup(): void
    {
        $this->backupTask->getAttribute('remoteServer');
        $backupDestinationModel = $this->backupTask->getAttribute('backupDestination');
        $sourcePath = $this->backupTask->getAttributeValue('source_path');
        $storagePath = $this->backupTask->getAttributeValue('store_path');

        $this->logMessage('Attempting to connect to remote server.');
        $sftp = $this->establishSFTPConnection($this->backupTask);
        $this->logMessage('Secure SSH connection established with the remote server.');

        if (! $this->checkPathExists($sftp, $sourcePath)) {
            throw new RuntimeException('The path specified does not exist.');
        }

        $this->backupTask->setScriptUpdateTime();

        $dirSize = $this->getRemoteDirectorySize($sftp, $sourcePath);
        $this->backupSize = $dirSize;
        $dirSizeInMB = number_format($dirSize / 1024 / 1024, 1);
        $this->logMessage(sprintf("Source directory '%s' size: %s MB.", $sourcePath, $dirSizeInMB));

        if ($dirSize > BackupConstants::FILE_SIZE_LIMIT) {
            throw new RuntimeException('Directory size exceeds the limit.');
        }

        $laravelProject = $this->isLaravelDirectory($sftp, $sourcePath);

        if ($laravelProject) {
            $this->logMessage('Laravel project detected. Optimizing backup process for Laravel-specific structure.');
        }

        $excludeDirs = $laravelProject ? ['node_modules', 'vendor'] : [];

        $zipFileName = $this->generateBackupFileName('zip');
        $remoteZipPath = '/tmp/' . $zipFileName;

        $this->zipRemoteDirectory($sftp, $sourcePath, $remoteZipPath, $excludeDirs);
        $this->logMessage(sprintf('Directory compression complete. Archive location: %s.', $remoteZipPath));

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
    }
}
