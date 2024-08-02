<?php

declare(strict_types=1);

namespace App\Livewire\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Manages the display of remote servers in a table format.
 *
 * This component handles the rendering and pagination of remote servers
 * for the authenticated user, excluding those marked for deletion.
 */
class IndexTable extends Component
{
    use WithPagination;

    /**
     * Render the remote servers index table.
     *
     * Fetches and paginates remote servers for the authenticated user,
     * excluding those marked for deletion.
     */
    public function render(): View
    {
        $remoteServers = RemoteServer::where('user_id', Auth::id())
            ->whereNull('marked_for_deletion_at')
            ->orderBy('created_at', 'desc')
            ->paginate(Auth::user()?->getAttribute('pagination_count') ?? 15, pageName: 'remote-servers');

        return view('livewire.remote-servers.index-table', ['remoteServers' => $remoteServers]);
    }
}
