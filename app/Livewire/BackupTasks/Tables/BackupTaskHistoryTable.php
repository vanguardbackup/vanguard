<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Tables;

use App\Models\BackupTaskLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Override;

class BackupTaskHistoryTable extends Component
{
    use WithPagination;

    /**
     * Unique key for the component instance.
     */
    public string $tableKey;

    /**
     * The current page number.
     */
    public int $page = 1;

    /**
     * The name of the pagination query string.
     */
    protected string $paginationQueryString = 'backup-task-logs';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->tableKey = 'backup-task-history-' . Auth::id();
        $this->page = (int) request()->query($this->paginationQueryString, '1');
    }

    /**
     * Define the query string parameters.
     *
     * @return array<string, array<int, string>>
     */
    public function queryString(): array
    {
        return [
            'page' => [0 => $this->paginationQueryString],
        ];
    }

    /**
     * Get the backup task logs.
     *
     * @return LengthAwarePaginator<BackupTaskLog>
     */
    #[Computed]
    public function backupTaskLogs(): LengthAwarePaginator
    {
        return BackupTaskLog::finished()
            ->with(['backupTask', 'backupTask.backupDestination', 'backupTask.RemoteServer'])
            ->whereHas('backupTask', function ($query): void {
                $query->where('user_id', Auth::id());
            })
            ->orderBy('created_at', 'desc')
            ->paginate(
                10,
                ['*'],
                $this->paginationQueryString,
                $this->page
            );
    }

    /**
     * Refresh the component data.
     */
    public function refreshData(): void
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    /**
     * Render the backup task history table.
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.tables.backup-task-history-table', [
            'backupTaskLogs' => $this->backupTaskLogs(),
        ]);
    }

    /**
     * Get the listeners array.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function getListeners(): array
    {
        return [
            'refreshBackupTaskHistory' => 'refreshData',
            'backup-task-status-changed' => 'refreshData',
        ];
    }
}
