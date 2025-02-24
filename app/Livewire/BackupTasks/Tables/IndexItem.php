<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Tables;

use Override;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Manages individual backup task items in the index table.
 *
 * This component handles the display and real-time updates of a single backup task.
 */
class IndexItem extends Component
{
    /** @var BackupTask The backup task being displayed */
    public BackupTask $backupTask;

    /** @var BackupTaskLog|null The latest log for the backup task */
    public ?BackupTaskLog $backupTaskLog = null;

    /**
     * Handle the CreatedBackupTaskLog event.
     *
     * Fetches the newly created log and updates the component.
     *
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

        $this->dispatch('backup-task-item-updated-' . $this->backupTask->getAttribute('id'));
        $this->dispatch('backup-task-status-changed', ['taskId' => $this->backupTask->getAttribute('id')]);
    }

    /**
     * Handle the BackupTaskStatusChanged event.
     *
     * Refreshes the component and related UI elements.
     */
    public function echoBackupTaskStatusReceivedEvent(): void
    {
        Log::debug('Received the BackupTaskStatusChanged event. Refreshing the component.');

        $this->dispatch('$refresh');
        $this->dispatch('update-run-button-' . $this->backupTask->getAttribute('id'));
        $this->dispatch('refresh-backup-tasks-table');
        $this->dispatch('backup-task-status-changed', ['taskId' => $this->backupTask->getAttribute('id')]);
    }

    /**
     * Initialize the component with a backup task.
     */
    public function mount(BackupTask $backupTask): void
    {
        $this->backupTask = $backupTask;
    }

    /**
     * Boot the component.
     *
     * Refreshes the backup task data and fetches the latest log.
     */
    public function boot(): void
    {
        /** @var BackupTask $freshBackupTask */
        $freshBackupTask = $this->backupTask->fresh();
        $this->backupTask = $freshBackupTask;

        $this->backupTaskLog = $this->backupTask->getAttribute('latestLog');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.tables.index-item');
    }

    /**
     * Get the event listeners for the component.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:new-backup-task-log.%s,CreatedBackupTaskLog', $this->backupTask->getAttribute('id')) => 'echoBackupTaskLogCreatedEvent',
            sprintf('echo-private:backup-tasks.%s,BackupTaskStatusChanged', $this->backupTask->getAttribute('id')) => 'echoBackupTaskStatusReceivedEvent',
            'task-button-clicked-' . $this->backupTask->getAttribute('id') => '$refresh',
            'toggle-pause-button-clicked-' . $this->backupTask->getAttribute('id') => '$refresh',
            'toggle-favourite-button-clicked-' . $this->backupTask->getAttribute('id') => '$refresh',
            'log-modal-updated-' . $this->backupTask->getAttribute('id') => '$refresh',
            'backup-task-status-changed' => '$refresh',
        ];
    }
}
