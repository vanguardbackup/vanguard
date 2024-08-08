<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BackupTaskLogResource;
use App\Models\BackupTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BackupTaskLatestLogController extends Controller
{
    /**
     * Retrieve the latest log for the specified backup task.
     */
    public function __invoke(Request $request, int $id): JsonResponse|JsonResource
    {
        $backupTask = BackupTask::find($id);

        if (! $backupTask) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Backup task not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (Gate::denies('view', $backupTask)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not authorized to view this backup task.',
            ], Response::HTTP_FORBIDDEN);
        }

        $latestLog = $backupTask->logs()->latest()->first();

        if (! $latestLog) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'No logs found for this backup task.',
            ], Response::HTTP_NOT_FOUND);
        }

        return new BackupTaskLogResource($latestLog);
    }
}
