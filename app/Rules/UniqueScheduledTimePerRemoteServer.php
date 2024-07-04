<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UniqueScheduledTimePerRemoteServer implements ValidationRule
{
    public function __construct(
        public int $remoteServerId,
        public ?int $taskId = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();

        $utcTime = $value;
        Log::info('UniqueScheduledTimePerRemoteServer: Time for comparison', ['time' => $utcTime]);

        $query = $user?->backupTasks()
            ->where('remote_server_id', $this->remoteServerId)
            ->where('time_to_run_at', $utcTime);

        if ($this->taskId !== null) {
            $query->where('id', '!=', $this->taskId);
        }

        $conflictingTask = $query->first();
        if ($conflictingTask) {
            Log::info('UniqueScheduledTimePerRemoteServer: Conflicting task found', [
                'conflicting_task_id' => $conflictingTask->id,
                'conflicting_task_time' => $conflictingTask->time_to_run_at,
            ]);
            $fail($this->message());
        } else {
            Log::info('UniqueScheduledTimePerRemoteServer: No conflicting task found');
        }
    }

    public function message(): string
    {
        return __('The scheduled time for this remote server is already taken. Please choose a different time.');
    }
}
