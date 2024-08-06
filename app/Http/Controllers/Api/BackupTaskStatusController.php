<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BackupTaskResource;
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
     * @return BackupTaskResource|JsonResponse The backup task resource or error response
     */
    public function __invoke(Request $request, int $id): BackupTaskResource|JsonResponse
    {
        $this->authorizeRequest($request, 'view-backup-tasks');

        $backupTask = $this->findBackupTask($id);

        if (! $backupTask instanceof BackupTask) {
            return response()->json(['message' => 'Backup task not found'], ResponseAlias::HTTP_NOT_FOUND);
        }

        Gate::authorize('view', $backupTask);

        return response()->json(['status' => $backupTask->getAttribute('status')]);
    }

    /**
     * Authorize the request based on the given ability.
     *
     * @param  Request  $request  The incoming request
     * @param  string  $ability  The ability to check
     */
    private function authorizeRequest(Request $request, string $ability): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan($ability)) {
            abort(403, 'Unauthorized action.');
        }
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
