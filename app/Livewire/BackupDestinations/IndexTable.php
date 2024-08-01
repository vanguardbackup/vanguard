<?php

declare(strict_types=1);

namespace App\Livewire\BackupDestinations;

use App\Models\BackupDestination;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class IndexTable extends Component
{
    use WithPagination;

    public function render(): View
    {
        $backupDestinations = BackupDestination::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(Auth::user()?->getAttribute('pagination_count') ?? 15, pageName: 'backup-destinations');

        return view('livewire.backup-destinations.index-table', ['backupDestinations' => $backupDestinations]);
    }
}
