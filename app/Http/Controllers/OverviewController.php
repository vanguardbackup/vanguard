<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BackupTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $logsCountPerMonthForLastSixMonths = BackupTask::logsCountPerMonthForLastSixMonths($user->getAttribute('id'));

        return view('dashboard', [
            'months' => json_encode(array_keys($logsCountPerMonthForLastSixMonths), JSON_THROW_ON_ERROR),
            'counts' => json_encode(array_values($logsCountPerMonthForLastSixMonths), JSON_THROW_ON_ERROR),
            'backupTasksCountByType' => BackupTask::backupTasksCountByType($user->getAttribute('id')),
        ]);
    }
}
