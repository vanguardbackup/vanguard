<?php

namespace App\Livewire\BackupTasks;

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class LogModal extends Component
{
    public BackupTask $backupTask;

    public ?BackupTaskLog $backupTaskLog = null;

    public string $logOutput = '';

    public bool $isWaiting = true;

    protected $listeners = [
        'refreshSelf',
        'updateLogOutput',
    ];

    public function mount(BackupTask $backupTask): void
    {
        $this->backupTask = $backupTask->fresh();
        $this->resetLog();
    }

    public function boot(): void
    {
        $this->resetLog();
    }

    public function updateLogOutput($event): void
    {
        Log::debug('Received the StreamBackupTaskLogEvent event. Updating log output.', ['event' => $event]);

        $newLogOutput = $event['logOutput'];

        // Avoid duplicate entries by checking if the new log output is already present in the current log output
        if (strpos($this->logOutput, $newLogOutput) === false) {
            // Append the new log output with a newline character if logOutput is not empty
            $this->logOutput .= ($this->logOutput ? "\n" : '').$newLogOutput;
        }

        Log::debug('Updated streamed log output:', ['logOutput' => $this->logOutput]);

        $this->isWaiting = false;

        $this->dispatch('$refresh');
        $this->dispatch('log-modal-updated-'.$this->backupTask->id);
    }

    public function refreshSelf(): void
    {
        Log::debug('A refresh event was dispatched from the parent component.', [
            'backupTaskId' => $this->backupTask->id,
            'backupTaskLogId' => $this->backupTaskLog->id ?? null,
        ]);

        $this->resetLog();
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.log-modal');
    }

    protected function getListeners(): array
    {
        return [
            "echo:backup-task-log.{$this->backupTask->id},StreamBackupTaskLogEvent" => 'updateLogOutput',
            "backup-task-item-updated-{$this->backupTask->id}" => 'refreshSelf',
        ];
    }

    private function resetLog(): void
    {
        $this->logOutput = '';
        $this->isWaiting = true;

        $this->loadLatestLog();
    }

    private function loadLatestLog(): void
    {
        $latestLog = $this->backupTask->logs()->latest()->first();

        if ($this->backupTask->isReady()) {
            $this->isWaiting = false;
            $this->logOutput = $latestLog?->output ?? __('Something went wrong while trying to fetch the log output.');
        }
    }
}
