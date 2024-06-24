<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks;

use App\Models\BackupTaskLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class BackupTaskHistory extends Component
{
    use WithPagination;

    public BackupTaskLog $backupTasks;

    public function render(): View
    {
        $backupTaskLogs = BackupTaskLog::finished()
            ->with(['backupTask', 'backupTask.backupDestination', 'backupTask.RemoteServer'])
            ->whereHas('backupTask', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->orderBy('created_at', 'desc')
            ->paginate(8, pageName: 'backup-task-logs');

        return view('livewire.backup-tasks.backup-task-history', [
            'backupTaskLogs' => $backupTaskLogs,
        ]);
    }

    /**
     * Get the listeners array.
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
