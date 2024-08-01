<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Tables;

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
            ->with(['remoteServer', 'backupDestination'])
            ->withAggregate('latestLog', 'created_at')
            ->orderBy('id', 'desc')
            ->paginate(Auth::user()?->getAttribute('pagination_count') ?? 15, pageName: 'backup-tasks');

        return view('livewire.backup-tasks.tables.index-table', ['backupTasks' => $backupTasks]);
    }
}
