<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Modals;

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

    /**
     * @var array<string>
     */
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

    /**
     * Update the log output with the new data from the event.
     *
     * @param  array<string, string>  $event
     */
    public function updateLogOutput(array $event): void
    {
        Log::debug('Received the StreamBackupTaskLogEvent event. Updating log output.', ['event' => $event]);

        $newLogOutput = $event['logOutput'];

        // Avoid duplicate entries by checking if the new log output is already present in the current log output
        if (! str_contains($this->logOutput, $newLogOutput)) {
            // Append the new log output with a newline character if logOutput is not empty
            $this->logOutput .= ($this->logOutput !== '' && $this->logOutput !== '0' ? "\n" : '') . $newLogOutput;
        }

        Log::debug('Updated streamed log output:', ['logOutput' => $this->logOutput]);

        $this->isWaiting = false;

        $this->dispatch('$refresh');
        $this->dispatch('log-modal-updated-' . $this->backupTask->getAttribute('id'));
    }

    public function refreshSelf(): void
    {
        Log::debug('A refresh event was dispatched from the parent component.', [
            'backupTaskId' => $this->backupTask->getAttribute('id'),
            'backupTaskLogId' => $this->backupTaskLog?->getAttribute('id'),
        ]);

        $this->resetLog();
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.modals.log-modal');
    }

    /**
     * Get the listeners array.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            "echo:backup-task-log.{$this->backupTask->getAttribute('id')},StreamBackupTaskLogEvent" => 'updateLogOutput',
            "backup-task-item-updated-{$this->backupTask->getAttribute('id')}" => 'refreshSelf',
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
