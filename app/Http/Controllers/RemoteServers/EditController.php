<?php

namespace App\Http\Controllers\RemoteServers;

use App\Http\Controllers\Controller;
use App\Models\RemoteServer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(Request $request, RemoteServer $remoteServer): View
    {
        return view('remote-servers.edit', [
            'remoteServer' => $remoteServer,
        ]);
    }
}
