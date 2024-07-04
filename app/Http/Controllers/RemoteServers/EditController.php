<?php

declare(strict_types=1);

namespace App\Http\Controllers\RemoteServers;

use App\Http\Controllers\Controller;
use App\Models\RemoteServer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(Request $request, RemoteServer $remoteServer): View
    {
        $remoteServer->query()
            ->whereNull('marked_for_deletion_at')
            ->findOrFail($remoteServer->getAttribute('id'));

        return view('remote-servers.edit', [
            'remoteServer' => $remoteServer,
        ]);
    }
}
