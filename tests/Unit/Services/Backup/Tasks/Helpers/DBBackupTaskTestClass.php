<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Backup\Tasks\Helpers;

use Override;
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

    #[Override]
    public function validateConfiguration(): void
    {
        // Do nothing
    }
}
