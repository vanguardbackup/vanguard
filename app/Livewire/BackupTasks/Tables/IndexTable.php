<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Tables;

use App\Models\BackupTask;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Manages the display of backup tasks in a table format.
 *
 * This component handles the rendering and pagination of backup tasks for the authenticated user.
 */
class IndexTable extends Component
{
    use WithPagination;

    /**
     * @var string[]
     */
    protected $listeners = ['refreshBackupTasksTable' => '$refresh'];

    /**
     * Render the backup tasks index table.
     *
     * Fetches and paginates backup tasks for the authenticated user, including related data.
     */
    public function render(): View
    {
        $backupTasks = BackupTask::where('user_id', Auth::id())
            ->with(['remoteServer', 'backupDestination'])
            ->withAggregate('latestLog', 'created_at')
            ->orderBy('id', 'desc')
            ->paginate(Auth::user()?->getAttribute('pagination_count') ?? 15, pageName: 'backup-tasks');

        return view('livewire.backup-tasks.tables.index-table', ['backupTasks' => $backupTasks]);
    }
}
