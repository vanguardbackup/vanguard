<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class UniqueScheduledTimePerRemoteServer implements ValidationRule
{
    public function __construct(public int $remoteServerId, public ?int $taskId = null)
    {
        //
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Auth::user()?->backupTasks()
            ->where('remote_server_id', $this->remoteServerId)
            ->where('time_to_run_at', $value);

        if ($this->taskId) {
            $query->where('id', '!=', $this->taskId);
        }

        if ($query->exists()) {
            $fail($this->message());
        }
    }

    public function message(): string
    {
        return __('The scheduled time for this remote server is already taken. Please choose a different time.');
    }
}
