<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackupTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * Handles retrieving the status of a backup task.
 */
class BackupTaskStatusController extends Controller
{
    /**
     * Retrieve the status of the specified backup task.
     *
     * @param  Request  $request  The incoming request
     * @param  int  $id  The ID of the backup task
     * @return JsonResponse The backup task status or error response
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $authResponse = $this->authorizeRequest($request, 'view-backup-tasks');
        if ($authResponse instanceof JsonResponse) {
            return $authResponse;
        }

        $backupTask = $this->findBackupTask($id);

        if (! $backupTask instanceof BackupTask) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Backup task not found',
            ], ResponseAlias::HTTP_NOT_FOUND);
        }

        if (Gate::denies('view', $backupTask)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not authorized to view this backup task',
            ], ResponseAlias::HTTP_FORBIDDEN);
        }

        return response()->json([
            'data' => [
                'id' => $backupTask->getAttribute('id'),
                'status' => $backupTask->getAttribute('status'),
            ],
        ]);
    }

    /**
     * Find a backup task by ID.
     *
     * @param  int  $id  The ID of the backup task
     * @return BackupTask|null The found backup task or null
     */
    private function findBackupTask(int $id): ?BackupTask
    {
        return BackupTask::find($id);
    }
}
