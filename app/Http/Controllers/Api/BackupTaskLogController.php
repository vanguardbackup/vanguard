<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BackupTaskLogResource;
use App\Models\BackupTaskLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Handles API operations for backup task logs.
 */
class BackupTaskLogController extends Controller
{
    /**
     * Display a paginated listing of the backup task logs.
     *
     * @param  Request  $request  The incoming request.
     * @return AnonymousResourceCollection A collection of backup task log resources.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeRequest($request, 'view-backup-tasks');

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $perPage = (int) $request->input('per_page', 15);
        $backupTaskLogs = BackupTaskLog::whereHas('backupTask', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })->paginate($perPage);

        return BackupTaskLogResource::collection($backupTaskLogs);
    }

    /**
     * Display the specified backup task log.
     *
     * @param  Request  $request  The incoming request.
     * @param  string  $id  The ID of the backup task log.
     * @return BackupTaskLogResource|JsonResponse The backup task log resource or a JSON response if not found.
     */
    public function show(Request $request, string $id): BackupTaskLogResource|JsonResponse
    {
        $this->authorizeRequest($request, 'view-backup-tasks');

        $backupTaskLog = $this->findBackupTaskLog($id);

        if (! $backupTaskLog instanceof BackupTaskLog) {
            return response()->json(['message' => 'Backup task log not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('view', $backupTaskLog->getAttribute('backupTask'));

        return new BackupTaskLogResource($backupTaskLog);
    }

    /**
     * Remove the specified backup task log from storage.
     *
     * @param  Request  $request  The incoming request.
     * @param  string  $id  The ID of the backup task log to delete.
     * @return Response|JsonResponse A response indicating success or failure.
     */
    public function destroy(Request $request, string $id): Response|JsonResponse
    {
        $this->authorizeRequest($request, 'delete-backup-tasks');

        $backupTaskLog = $this->findBackupTaskLog($id);

        if (! $backupTaskLog instanceof BackupTaskLog) {
            return response()->json(['message' => 'Backup task log not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('forceDelete', $backupTaskLog->getAttribute('backupTask'));

        $backupTaskLog->delete();

        return response()->noContent();
    }

    /**
     * Authorize the request based on the given ability.
     *
     * @param  Request  $request  The incoming request.
     * @param  string  $ability  The ability to check for authorization.
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
     * Find a backup task log by ID.
     *
     * @param  string  $id  The ID of the backup task log to find.
     * @return BackupTaskLog|null The found backup task log or null if not found.
     */
    private function findBackupTaskLog(string $id): ?BackupTaskLog
    {
        return BackupTaskLog::find($id);
    }
}
