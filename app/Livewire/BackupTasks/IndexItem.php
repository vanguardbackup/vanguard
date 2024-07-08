<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks;

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class IndexItem extends Component
{
    public BackupTask $backupTask;
    public ?BackupTaskLog $backupTaskLog;

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            "echo-private:new-backup-task-log.{$this->backupTask->getAttribute('id')},CreatedBackupTaskLog" => 'echoBackupTaskLogCreatedEvent',
            "echo-private:backup-tasks.{$this->backupTask->getAttribute('id')},BackupTaskStatusChanged" => 'echoBackupTaskStatusReceivedEvent',

            // Refresh the component when the following events are dispatched, so the status of the table row changes.
            "task-button-clicked-{$this->backupTask->getAttribute('id')}" => '$refresh',
            "pause-button-clicked-{$this->backupTask->getAttribute('id')}" => '$refresh',
            "log-modal-updated-{$this->backupTask->getAttribute('id')}" => '$refresh',
        ];
    }

    /**
     * @param  array<string, mixed>  $event
     */
    public function echoBackupTaskLogCreatedEvent(array $event): void
    {
        Log::debug('Received the CreatedBackupTaskLog event. Fetching the log.', ['new_log_id' => $event['logId']]);
        $this->backupTaskLog = BackupTaskLog::findOrFail($event['logId']);

        // Refresh the component and fetch the latest log.
        $this->dispatch('backup-task-item-updated-' . $this->backupTask->getAttribute('id'));
    }

    public function echoBackupTaskStatusReceivedEvent(): void
    {
        Log::debug('Received the BackupTaskStatusChanged event. Refreshing the component.');

        $this->dispatch('$refresh');
        $this->dispatch('update-run-button-' . $this->backupTask->getAttribute('id'));

        $this->dispatch('refresh-backup-tasks-table'); // We want to refresh the index table since buttons will be disabled after task run.
        $this->dispatch('refreshBackupTaskHistory'); // Refresh the backup task history component as there's a new log.
    }

    public function mount(BackupTask $backupTask): void
    {
        $this->backupTask = $backupTask;
    }

    public function boot(): void
    {
        $this->backupTask = $this->backupTask->fresh();

        // This needs to be here to fetch the latest log.
        $this->backupTaskLog = $this->backupTask->logs->last();
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.index-item');
    }
}
