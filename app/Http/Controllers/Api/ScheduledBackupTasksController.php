<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\LoadScheduledBackupTasksAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduledBackupTaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScheduledBackupTasksController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, LoadScheduledBackupTasksAction $loadScheduledBackupTasksAction): AnonymousResourceCollection
    {
        $scheduledBackupTasks = $loadScheduledBackupTasksAction->execute();

        return ScheduledBackupTaskResource::collection($scheduledBackupTasks);
    }
}
