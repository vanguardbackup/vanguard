<?php

declare(strict_types=1);

namespace App\Http\Controllers\RemoteServers;

use App\Http\Controllers\Controller;
use App\Models\RemoteServer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for editing remote servers.
 *
 * This controller handles the display of the edit form for a specific remote server,
 * ensuring that only non-deleted servers can be edited.
 */
class EditController extends Controller
{
    /**
     * Display the edit form for the specified remote server.
     *
     * This method checks if the server is not marked for deletion before displaying the edit form.
     * If the server is marked for deletion, it will throw a 404 Not Found exception.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  RemoteServer  $remoteServer  The remote server to be edited.
     * @return View The view containing the edit form.
     *
     * @throws ModelNotFoundException If the server is marked for deletion.
     */
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
