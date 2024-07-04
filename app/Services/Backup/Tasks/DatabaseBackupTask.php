<?php

declare(strict_types=1);

namespace App\Services\Backup\Tasks;

use App\Exceptions\DatabaseDumpException;
use App\Exceptions\SFTPConnectionException;
use RuntimeException;

class DatabaseBackupTask extends AbstractBackupTask
{
    /**
     * @throws DatabaseDumpException
     * @throws SFTPConnectionException
     */
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

        $sftp = $this->establishSFTPConnection($remoteServer, $this->backupTask);
        $this->logMessage('SSH Connection established to the server.');

        $databaseType = $this->getDatabaseType($sftp);
        $this->logMessage("Database type detected: {$databaseType}.");

        $this->backupTask->setScriptUpdateTime();

        $dumpFileName = $this->generateBackupFileName('sql');
        $remoteDumpPath = "/tmp/{$dumpFileName}";

        $this->dumpRemoteDatabase($sftp, $databaseType, $remoteDumpPath, $databasePassword, $databaseName, $this->backupTask->getAttribute('excluded_database_tables'));

        if (! $this->backupDestinationDriver($backupDestinationModel->type, $sftp, $remoteDumpPath, $backupDestinationModel, $dumpFileName, $storagePath)) {
            throw new RuntimeException('Failed to upload the dump file to destination.');
        }

        $this->backupTask->setScriptUpdateTime();

        if ($this->backupTask->isRotatingBackups()) {
            $backupDestination = $this->createBackupDestinationInstance($backupDestinationModel);
            $this->rotateOldBackups($backupDestination, $this->backupTask->getAttribute('id'), $this->backupTask->getAttribute('maximum_backups_to_keep'), '.sql', 'backup_');
        }

        $this->logMessage("Database backup has been uploaded to {$backupDestinationModel->label} - {$backupDestinationModel->type()}: {$dumpFileName}");

        $sftp->delete($remoteDumpPath);
        $this->logMessage('Cleaned up the temporary file on the server.');
    }
}
