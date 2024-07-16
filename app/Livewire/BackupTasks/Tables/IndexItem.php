<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Tables;

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class IndexItem extends Component
{
    public BackupTask $backupTask;

    public ?BackupTaskLog $backupTaskLog = null;

    /**
     * @param  array<string, mixed>  $event
     */
    public function echoBackupTaskLogCreatedEvent(array $event): void
    {
        Log::debug('Received the CreatedBackupTaskLog event. Fetching the log.', ['new_log_id' => $event['logId']]);

        $log = BackupTaskLog::find($event['logId']);

        if ($log instanceof BackupTaskLog) {
            $this->backupTaskLog = $log;
        } else {
            Log::warning('BackupTaskLog not found', ['logId' => $event['logId']]);
            $this->backupTaskLog = null;
        }

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

    /**
     * Boot the component.
     */
    public function boot(): void
    {
        /** @var BackupTask $freshBackupTask */
        $freshBackupTask = $this->backupTask->fresh();
        $this->backupTask = $freshBackupTask;

        // This needs to be here to fetch the latest log.
        $this->backupTaskLog = $this->backupTask->getAttribute('latestLog');
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.tables.index-item');
    }

    /**
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:new-backup-task-log.%s,CreatedBackupTaskLog', $this->backupTask->getAttribute('id')) => 'echoBackupTaskLogCreatedEvent',
            sprintf('echo-private:backup-tasks.%s,BackupTaskStatusChanged', $this->backupTask->getAttribute('id')) => 'echoBackupTaskStatusReceivedEvent',

            // Refresh the component when the following events are dispatched, so the status of the table row changes.
            'task-button-clicked-' . $this->backupTask->getAttribute('id') => '$refresh',
            'pause-button-clicked-' . $this->backupTask->getAttribute('id') => '$refresh',
            'log-modal-updated-' . $this->backupTask->getAttribute('id') => '$refresh',
        ];
    }
}
