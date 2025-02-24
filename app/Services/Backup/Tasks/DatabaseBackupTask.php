<?php

declare(strict_types=1);

namespace App\Services\Backup\Tasks;

use App\Exceptions\DatabaseDumpException;
use App\Exceptions\SFTPConnectionException;
use Override;
use RuntimeException;

/**
 * DatabaseBackupTask
 *
 * This class extends AbstractBackupTask to provide specific implementation
 * for backing up databases. It handles the process of connecting to a remote server,
 * dumping the database, and storing the backup in a specified destination.
 */
class DatabaseBackupTask extends AbstractBackupTask
{
    /**
     * Perform the database backup.
     *
     * This method executes the following steps:
     * 1. Establishes an SFTP connection to the remote server.
     * 2. Detects the database type.
     * 3. Determines the size of the database.
     * 4. Dumps the remote database to a temporary file.
     * 5. Uploads the dump file to the specified backup destination.
     * 6. Optionally rotates old backups based on configuration.
     * 7. Cleans up temporary files.
     *
     * @throws DatabaseDumpException If there's an error during the database dump process
     * @throws SFTPConnectionException If there's an error establishing the SFTP connection
     * @throws RuntimeException If there's a failure in the backup process or if the database password is missing
     */
    #[Override]
    protected function performBackup(): void
    {
        $remoteServer = $this->backupTask->getAttribute('remoteServer');
        $backupDestinationModel = $this->backupTask->getAttribute('backupDestination');
        $databaseName = $this->backupTask->getAttribute('database_name');
        $databasePassword = $remoteServer->getDecryptedDatabasePassword();
        $storagePath = $this->backupTask->getAttributeValue('store_path');

        if (! $remoteServer->hasDatabasePassword()) {
            throw new RuntimeException('Please provide a database password for the remote server.');
        }

        $this->logMessage('Attempting to connect to remote server.');
        $sftp = $this->establishSFTPConnection($this->backupTask);
        $this->logMessage('Secure SSH connection established with the remote server.');

        $databaseType = $this->getDatabaseType($sftp);
        $this->logMessage('Detected database type: ' . ucfirst($databaseType) . '.');

        $this->backupTask->setScriptUpdateTime();

        $dumpFileName = $this->generateBackupFileName('sql');
        $remoteDumpPath = '/tmp/' . $dumpFileName;

        $dirSize = $this->getRemoteDatabaseSize($sftp, $databaseType, $databaseName, $databasePassword);
        $this->backupSize = $dirSize;

        $this->dumpRemoteDatabase($sftp, $databaseType, $remoteDumpPath, $databasePassword, $databaseName, $this->backupTask->getAttribute('excluded_database_tables'));

        if ($this->backupTask->hasEncryptionPassword()) {
            $this->setFileEncryption($sftp, $remoteDumpPath);
        }

        if (! $this->backupDestinationDriver($backupDestinationModel->type, $sftp, $remoteDumpPath, $backupDestinationModel, $dumpFileName, $storagePath)) {
            throw new RuntimeException('Failed to upload the dump file to destination.');
        }

        $this->backupTask->setScriptUpdateTime();

        // NOTE: Vanguard doesn't support backup rotations on the local driver due to how the abstract class methods are set up.
        // It is so heavily coupled to assuming it's a third party driver that it will be a nuisance to sort.
        // It will need to be addressed.
        if ($this->backupTask->isRotatingBackups() && ! $backupDestinationModel->isLocalConnection()) {
            $backupDestination = $this->createBackupDestinationInstance($backupDestinationModel);
            $this->rotateOldBackups($backupDestination, $this->backupTask->getAttribute('id'), $this->backupTask->getAttribute('maximum_backups_to_keep'), '.sql', 'backup_');
            $this->logMessage(sprintf('Initiating backup rotation. Retention limit: %s backups.', $this->backupTask->getAttribute('maximum_backups_to_keep')));
        }

        $this->logMessage(sprintf('Database backup has been uploaded to %s - %s: %s', $backupDestinationModel->label, $backupDestinationModel->type(), $dumpFileName));

        $sftp->delete($remoteDumpPath);
        $this->logMessage('Temporary server file removed after successful backup operation.');
    }
}
