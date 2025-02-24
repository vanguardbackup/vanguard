<?php

declare(strict_types=1);

namespace App\Rules;

use Override;
use App\Models\BackupTask;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Validation rule to ensure unique scheduled times per remote server.
 *
 * This rule checks if a given time is already scheduled for a specific
 * remote server, considering the current user's backup tasks.
 */
readonly class UniqueScheduledTimePerRemoteServer implements ValidationRule
{
    public function __construct(
        public int $remoteServerId,
        public ?int $taskId = null,
    ) {}

    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user) {
            $fail($this->message());

            return;
        }

        $utcTime = $value;
        Log::debug('UniqueScheduledTimePerRemoteServer: Time for comparison', ['time' => $utcTime]);

        $conflictingTask = $this->findConflictingTask($user, $utcTime);

        if (! $conflictingTask instanceof BackupTask) {
            Log::debug('UniqueScheduledTimePerRemoteServer: No conflicting task found');

            return;
        }

        Log::debug('UniqueScheduledTimePerRemoteServer: Conflicting task found', [
            'conflicting_task_id' => $conflictingTask->getAttribute('id'),
            'conflicting_task_time' => $conflictingTask->getAttribute('time_to_run_at'),
        ]);
        $fail($this->message());
    }

    public function message(): string
    {
        return __('The scheduled time for this remote server is already taken. Please choose a different time.');
    }

    /**
     * Find a conflicting backup task for the given user and time.
     *
     * @param  User  $user  The user to check tasks for
     * @param  mixed  $utcTime  The time to check for conflicts
     * @return BackupTask|null The conflicting task, if any
     */
    private function findConflictingTask(User $user, mixed $utcTime): ?BackupTask
    {
        $builder = $user->backupTasks()
            ->where('remote_server_id', $this->remoteServerId)
            ->where('time_to_run_at', $utcTime);

        if ($this->taskId !== null) {
            $builder->where('id', '!=', $this->taskId);
        }

        return $builder->first();
    }
}
