<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Tables;

use App\Models\BackupTaskLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Manages the display of backup task history.
 *
 * This component handles the rendering and pagination of completed backup task logs.
 */
class BackupTaskHistoryTable extends Component
{
    use WithPagination;

    /** @var BackupTaskLog Collection of backup task logs */
    public BackupTaskLog $backupTasks;

    /**
     * Render the backup task history table.
     *
     * Fetches and paginates finished backup task logs for the authenticated user.
     */
    public function render(): View
    {
        $backupTaskLogs = BackupTaskLog::finished()
            ->with(['backupTask', 'backupTask.backupDestination', 'backupTask.RemoteServer'])
            ->whereHas('backupTask', function ($query): void {
                $query->where('user_id', Auth::id());
            })
            ->orderBy('created_at', 'desc')
            ->paginate(Auth::user()?->getAttribute('pagination_count') ?? 15, pageName: 'backup-task-logs');

        return view('livewire.backup-tasks.tables.backup-task-history-table', [
            'backupTaskLogs' => $backupTaskLogs,
        ]);
    }

    /**
     * Get the listeners array.
     *
     * Defines the event listeners for this component.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'refreshBackupTaskHistory' => '$refresh',
        ];
    }
}
