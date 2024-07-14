<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Modals;

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\On;

class LogModal extends Component
{
    public int $backupTaskId;
    public ?string $logOutput = null;
    public bool $isStreaming = false;
    public bool $isLoading = true;

    public function mount($backupTask): void
    {
        $this->backupTaskId = $backupTask instanceof BackupTask ? $backupTask->id : $backupTask;
        $this->loadLatestLog();
    }

    #[On('echo:backup-task-log.{backupTaskId},StreamBackupTaskLogEvent')]
    public function handleStreamEvent($event): void
    {
        Log::debug('LogModal: Received StreamBackupTaskLogEvent', ['event' => $event, 'componentId' => $this->getId()]);

        $this->logOutput = $event['logOutput'];
        $this->isStreaming = true;
        $this->isLoading = false;
    }

    public function refresh(): void
    {
        $this->loadLatestLog();
    }

    private function loadLatestLog(): void
    {
        $this->isLoading = true;
        $backupTask = BackupTask::find($this->backupTaskId);

        if ($backupTask) {
            $latestLog = $backupTask->logs()->latest()->first();
            $this->logOutput = $latestLog?->output ?? __('No log output available.');
            $this->isStreaming = $backupTask->status === BackupTask::STATUS_RUNNING;
        } else {
            $this->logOutput = __('Backup task not found.');
            $this->isStreaming = false;
        }

        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.backup-tasks.modals.log-modal', [
            'backupTask' => BackupTask::find($this->backupTaskId),
        ]);
    }
}
