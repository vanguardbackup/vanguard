<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackupTask;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Handles the execution of backup tasks via API requests.
 *
 * This controller is responsible for validating and initiating
 * backup tasks based on user permissions and task status.
 * It implements rate limiting to prevent abuse.
 */
class RunBackupTaskController extends Controller
{
    /**
     * The maximum number of attempts allowed within the time frame.
     */
    private const int MAX_ATTEMPTS = 5;

    /**
     * The time frame for rate limiting in minutes.
     */
    private const int DECAY_MINUTES = 1;

    /**
     * Execute the specified backup task.
     *
     * This method implements rate limiting to prevent abuse.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  int  $id  The ID of the backup task to run
     * @return JsonResponse The JSON response indicating the result of the operation
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->checkRateLimit($user->getAttribute('id'))) {
            return $this->jsonResponse('Too many requests. Please try again later.', 429);
        }

        $backupTask = BackupTask::find($id);

        if (! $backupTask) {
            return $this->jsonResponse('Backup task not found', 404);
        }

        if (! $user->tokenCan('run-backup-tasks')) {
            return $this->jsonResponse('Access denied. Your token does not have permission to run backup tasks.', 403);
        }

        if ($backupTask->user_id !== $user->getAttribute('id')) {
            return $this->jsonResponse('Access denied. This backup task does not belong to you.', 403);
        }

        if ($backupTask->isPaused()) {
            return $this->jsonResponse('The backup task is currently paused and cannot be executed.', 409);
        }

        if ($backupTask->isAnotherTaskRunningOnSameRemoteServer()) {
            return $this->jsonResponse('Another task is currently running on the same remote server. Please try again later.', 409);
        }

        $backupTask->markAsRunning();
        $backupTask->run();

        return $this->jsonResponse('Backup task initiated successfully.', 202);
    }

    /**
     * Check if the user has exceeded the rate limit.
     *
     * @param  int  $userId  The ID of the user to check
     * @return bool True if the request is allowed, false if it exceeds the rate limit
     */
    private function checkRateLimit(int $userId): bool
    {
        $key = 'run_backup_task_' . $userId;

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return false;
        }

        RateLimiter::hit($key, self::DECAY_MINUTES * 60);

        return true;
    }

    /**
     * Create a JSON response with the given message and status code.
     *
     * @param  string  $message  The message to include in the response
     * @param  int  $statusCode  The HTTP status code for the response
     * @return JsonResponse The formatted JSON response
     */
    private function jsonResponse(string $message, int $statusCode): JsonResponse
    {
        return response()->json(['message' => $message], $statusCode);
    }
}
