<?php

declare(strict_types=1);

namespace App\Http\Controllers\BackupTasks;

use App\Http\Controllers\Controller;
use App\Models\BackupTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;

class TriggerRunWebhookController extends Controller
{
    /**
     * The maximum number of attempts allowed within the time frame.
     */
    private const int MAX_ATTEMPTS = 10;

    /**
     * The time frame for rate limiting in minutes.
     */
    private const int DECAY_MINUTES = 1;

    /**
     * Handle the incoming webhook request.
     */
    public function __invoke(Request $request, BackupTask $backupTask): JsonResponse
    {
        // Validate the token
        if ($request->query('token') !== $backupTask->getAttribute('webhook_token')) {
            Log::warning('Invalid backup task webhook token', [
                'task_id' => $backupTask->getKey(),
                'ip' => $request->ip(),
            ]);

            return Response::json(['message' => 'Invalid token'], 403);
        }

        $key = "webhook_run_backup_task_{$backupTask->getKey()}";

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return Response::json(['message' => 'Too many requests. Please try again later.'], 429);
        }

        RateLimiter::hit($key, self::DECAY_MINUTES * 60);

        if ($backupTask->isPaused()) {
            return Response::json(['message' => 'The backup task is currently paused and cannot be executed.'], 409);
        }

        if ($backupTask->isAnotherTaskRunningOnSameRemoteServer()) {
            return Response::json(['message' => 'Another task is currently running on the same remote server. Please try again later.'], 409);
        }

        if ($backupTask->isRunning()) {
            return Response::json(['message' => 'The backup task is already running.'], 409);
        }

        $backupTask->markAsRunning();
        $backupTask->run();

        return Response::json(['message' => 'Backup task initiated successfully.'], 202);
    }
}
