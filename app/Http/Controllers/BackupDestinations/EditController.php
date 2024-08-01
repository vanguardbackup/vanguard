<?php

declare(strict_types=1);

namespace App\Http\Controllers\BackupDestinations;

use App\Http\Controllers\Controller;
use App\Models\BackupDestination;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for editing backup destinations.
 *
 * This controller handles the display of the edit form for a specific backup destination.
 */
class EditController extends Controller
{
    /**
     * Show the edit form for the specified backup destination.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  BackupDestination  $backupDestination  The backup destination to be edited.
     * @return View The view containing the edit form.
     */
    public function __invoke(Request $request, BackupDestination $backupDestination): View
    {
        return view('backup-destinations.edit', [
            'backupDestination' => $backupDestination,
        ]);
    }
}
