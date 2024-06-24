<?php

declare(strict_types=1);

namespace App\Http\Controllers\BackupTasks;

use App\Http\Controllers\Controller;
use App\Models\BackupTask;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(Request $request, BackupTask $backupTask): View
    {
        return view('backup-tasks.edit', [
            'backupTask' => $backupTask,
        ]);
    }
}
