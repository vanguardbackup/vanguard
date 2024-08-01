<?php

declare(strict_types=1);

namespace App\Http\Controllers\BackupTasks;

use App\Http\Controllers\Controller;
use App\Models\BackupTask;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for editing backup tasks.
 *
 * This controller is responsible for displaying the edit form
 * for a specific backup task.
 */
class EditController extends Controller
{
    /**
     * Display the edit form for the specified backup task.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  BackupTask  $backupTask  The backup task to be edited.
     * @return View The view containing the edit form.
     */
    public function __invoke(Request $request, BackupTask $backupTask): View
    {
        return view('backup-tasks.edit', [
            'backupTask' => $backupTask,
        ]);
    }
}
