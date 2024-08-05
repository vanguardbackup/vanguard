<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RemoteServerResource;
use App\Models\RemoteServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;

class RemoteServerController extends Controller
{
    /**
     * Display a paginated listing of the remote servers.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('manage-remote-servers')) {
            abort(403, 'Unauthorized action.');
        }

        $perPage = $request->input('per_page', 15);
        $remoteServers = RemoteServer::where('user_id', $user->id)->paginate($perPage);

        return RemoteServerResource::collection($remoteServers);
    }

    /**
     * Store a newly created remote server in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('manage-remote-servers')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'label' => ['required', 'string'],
            'ip_address' => ['required', 'string', 'unique:remote_servers,ip_address', 'ip'],
            'username' => ['required', 'string'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'database_password' => ['nullable', 'string'],
        ]);

        if (isset($validated['database_password'])) {
            $validated['database_password'] = Crypt::encryptString($validated['database_password']);
        }

        $remoteServer = RemoteServer::create($validated + [
            'user_id' => $user->id,
            'connectivity_status' => RemoteServer::STATUS_UNKNOWN,
        ]);

        return (new RemoteServerResource($remoteServer))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified remote server.
     */
    public function show(Request $request, RemoteServer $remoteServer): RemoteServerResource
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('manage-remote-servers')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('view', $remoteServer);

        return new RemoteServerResource($remoteServer);
    }

    /**
     * Update the specified remote server in storage.
     */
    public function update(Request $request, RemoteServer $remoteServer): RemoteServerResource
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('manage-remote-servers')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('update', $remoteServer);

        $validated = $request->validate([
            'label' => ['sometimes', 'required', 'string'],
            'ip_address' => ['sometimes', 'required', 'string', 'ip', 'unique:remote_servers,ip_address,' . $remoteServer->getAttribute('id')],
            'username' => ['sometimes', 'required', 'string'],
            'port' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'database_password' => ['nullable', 'string'],
        ]);

        if (isset($validated['database_password'])) {
            $validated['database_password'] = Crypt::encryptString($validated['database_password']);
        } else {
            unset($validated['database_password']);
        }

        $remoteServer->update($validated);

        return new RemoteServerResource($remoteServer);
    }

    /**
     * Remove the specified remote server from storage.
     */
    public function destroy(Request $request, RemoteServer $remoteServer): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('manage-remote-servers')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('forceDelete', $remoteServer);

        $remoteServer->delete();

        return response()->noContent();
    }
}
