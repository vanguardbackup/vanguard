<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Modals;

use App\Models\BackupTask;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class LogModal extends Component
{
    public int $backupTaskId;
    public ?string $logOutput = null;
    public bool $isStreaming = false;
    public bool $isLoading = true;

    public function mount(BackupTask|int $backupTask): void
    {
        $this->backupTaskId = $backupTask instanceof BackupTask ? $backupTask->getAttribute('id') : $backupTask;
        $this->loadLatestLog();
    }

    /**
     * Handle the stream event.
     *
     * @param  array{logOutput: string}  $event
     */
    #[On('echo:backup-task-log.{backupTaskId},StreamBackupTaskLogEvent')]
    public function handleStreamEvent(array $event): void
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

    public function render(): View
    {
        return view('livewire.backup-tasks.modals.log-modal', [
            'backupTask' => BackupTask::find($this->backupTaskId),
        ]);
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
}
