<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Modals;

use App\Models\BackupTask;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Manages the display of backup task logs in a modal.
 *
 * This component handles the rendering and real-time updates of backup task logs.
 */
class LogModal extends Component
{
    /** @var int The ID of the backup task */
    public int $backupTaskId;

    /** @var string|null The current log output */
    public ?string $logOutput = null;

    /** @var bool Indicates if the log is currently streaming */
    public bool $isStreaming = false;

    /** @var bool Indicates if the log is currently loading */
    public bool $isLoading = true;

    /**
     * Initialize the component with a backup task.
     */
    public function mount(BackupTask|int $backupTask): void
    {
        $this->backupTaskId = $backupTask instanceof BackupTask ? $backupTask->getAttribute('id') : $backupTask;
        $this->loadLatestLog();
    }

    /**
     * Handle the stream event for real-time log updates.
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

    /**
     * Refresh the log output.
     */
    public function refresh(): void
    {
        $this->loadLatestLog();
    }

    /**
     * Render the log modal component.
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.modals.log-modal', [
            'backupTask' => BackupTask::find($this->backupTaskId),
        ]);
    }

    /**
     * Load the latest log for the backup task.
     */
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
