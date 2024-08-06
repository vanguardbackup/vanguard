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
        $this->authorizeRequest($request, 'view-backup-tasks');

        $backupTask = BackupTask::find($id);

        if (! $backupTask) {
            return response()->json(['message' => 'Backup task not found.'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('view', $backupTask);

        $latestLog = $backupTask->logs()->latest()->first();

        if (! $latestLog) {
            return response()->json(['message' => 'No logs found for this backup task.'], Response::HTTP_NOT_FOUND);
        }

        return new BackupTaskLogResource($latestLog);
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
}
