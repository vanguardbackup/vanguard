<?php

namespace Tests\Unit\Services\Backup\Tasks\Helpers;

use App\Models\BackupTask;
use App\Services\Backup\Tasks\DatabaseBackupTask;

class DBBackupTaskTestClass extends DatabaseBackupTask
{
    public function __construct($backupTaskId)
    {
        $this->backupTask = BackupTask::findOrFail($backupTaskId);
        $this->scriptRunTime = microtime(true);
        $this->logOutput = '';
    }

    public function validateConfiguration(): void
    {
        // Do nothing
    }
}
