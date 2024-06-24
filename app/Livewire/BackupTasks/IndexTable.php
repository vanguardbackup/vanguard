<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks;

use App\Models\BackupTask;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class IndexTable extends Component
{
    use WithPagination;

    public function render(): View
    {
        $backupTasks = BackupTask::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10, pageName: 'backup-tasks');

        return view('livewire.backup-tasks.index-table', ['backupTasks' => $backupTasks]);
    }

    /**
     * Get the listeners array.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'refresh-backup-tasks-table' => '$refresh',
        ];
    }
}
