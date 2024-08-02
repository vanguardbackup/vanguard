<?php

declare(strict_types=1);

namespace App\Livewire\BackupDestinations;

use App\Models\BackupDestination;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Livewire component for displaying a table of backup destinations.
 *
 * This component handles the pagination and rendering of backup destinations
 * for the authenticated user.
 */
class IndexTable extends Component
{
    use WithPagination;

    /**
     * Render the component.
     *
     * Fetches paginated backup destinations for the authenticated user
     * and passes them to the view.
     */
    public function render(): View
    {
        $backupDestinations = BackupDestination::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(Auth::user()?->getAttribute('pagination_count') ?? 15, pageName: 'backup-destinations');

        return view('livewire.backup-destinations.index-table', ['backupDestinations' => $backupDestinations]);
    }
}
