<?php

namespace App\Livewire\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class IndexTable extends Component
{
    use withPagination;

    public function render(): View
    {
        $remoteServers = RemoteServer::where('user_id', Auth::id())
            ->whereNull('marked_for_deletion_at')
            ->orderBy('created_at', 'desc')
            ->paginate(30, pageName: 'remote-servers');

        return view('livewire.remote-servers.index-table', ['remoteServers' => $remoteServers]);
    }
}
