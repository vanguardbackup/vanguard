<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BackupTaskLogResource;
use App\Models\BackupTaskLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
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
     * @return AnonymousResourceCollection|JsonResponse A collection of backup task log resources or an error response.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $perPage = (int) $request->input('per_page', 15);
        $backupTaskLogs = BackupTaskLog::whereHas('backupTask', function ($query) use ($user): void {
            $query->where('user_id', $user->getAttribute('id'));
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
        $backupTaskLog = $this->findBackupTaskLog($id);

        if (! $backupTaskLog instanceof BackupTaskLog) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Backup task log not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (Gate::denies('view', $backupTaskLog->getAttribute('backupTask'))) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not authorized to view this backup task log',
            ], Response::HTTP_FORBIDDEN);
        }

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
        $backupTaskLog = $this->findBackupTaskLog($id);

        if (! $backupTaskLog instanceof BackupTaskLog) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Backup task log not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (Gate::denies('forceDelete', $backupTaskLog->getAttribute('backupTask'))) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not authorized to delete this backup task log',
            ], Response::HTTP_FORBIDDEN);
        }

        $backupTaskLog->delete();

        return response()->noContent();
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
