<?php

declare(strict_types=1);

namespace App\Http\Controllers\BackupDestinations;

use App\Http\Controllers\Controller;
use App\Models\BackupDestination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(Request $request, BackupDestination $backupDestination): View
    {
        return view('backup-destinations.edit', [
            'backupDestination' => $backupDestination,
        ]);
    }
}
