<?php

namespace App\Http\Controllers;

use App\Models\BackupTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        $logsCountPerMonthForLastSixMonths = BackupTask::logsCountPerMonthForLastSixMonths(Auth::user()->id);

        return view('dashboard', [
            'months' => json_encode(array_keys($logsCountPerMonthForLastSixMonths), JSON_THROW_ON_ERROR),
            'counts' => json_encode(array_values($logsCountPerMonthForLastSixMonths), JSON_THROW_ON_ERROR),
            'backupTasksCountByType' => BackupTask::backupTasksCountByType(Auth::user()->id),
        ]);
    }
}
