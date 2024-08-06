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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Manages API operations for remote servers.
 */
class RemoteServerController extends Controller
{
    /**
     * Display a paginated listing of the remote servers.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeRequest($request, 'manage-remote-servers');

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $perPage = (int) $request->input('per_page', 15);
        $remoteServers = RemoteServer::where('user_id', $user->id)->paginate($perPage);

        return RemoteServerResource::collection($remoteServers);
    }

    /**
     * Store a newly created remote server in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeRequest($request, 'manage-remote-servers');

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        try {
            $validated = $this->validateRemoteServer($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

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
    public function show(Request $request, mixed $id): RemoteServerResource|JsonResponse
    {
        $this->authorizeRequest($request, 'manage-remote-servers');

        $remoteServer = $this->findRemoteServer($id);

        if (! $remoteServer instanceof RemoteServer) {
            return response()->json(['message' => 'Remote server not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('view', $remoteServer);

        return new RemoteServerResource($remoteServer);
    }

    /**
     * Update the specified remote server in storage.
     */
    public function update(Request $request, mixed $id): RemoteServerResource|JsonResponse
    {
        $this->authorizeRequest($request, 'manage-remote-servers');

        $remoteServer = $this->findRemoteServer($id);

        if (! $remoteServer instanceof RemoteServer) {
            return response()->json(['message' => 'Remote server not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('update', $remoteServer);

        try {
            $validated = $this->validateRemoteServer($request, true, $remoteServer->getAttribute('id'));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

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
    public function destroy(Request $request, mixed $id): Response|JsonResponse
    {
        $this->authorizeRequest($request, 'manage-remote-servers');

        $remoteServer = $this->findRemoteServer($id);

        if (! $remoteServer instanceof RemoteServer) {
            return response()->json(['message' => 'Remote server not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('forceDelete', $remoteServer);

        $remoteServer->delete();

        return response()->noContent();
    }

    /**
     * Authorize the request based on the given ability.
     */
    private function authorizeRequest(Request $request, string $ability): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan($ability)) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Validate the remote server data.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validateRemoteServer(Request $request, bool $isUpdate = false, ?int $remoteServerId = null): array
    {
        $rules = [
            'label' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'string', 'ip'],
            'username' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'database_password' => ['nullable', 'string', 'max:255'],
        ];

        if (! $isUpdate) {
            $rules['ip_address'][] = 'unique:remote_servers,ip_address';
        } else {
            $rules = array_map(fn (array $rule): array => array_merge(['sometimes'], $rule), $rules);
            $rules['ip_address'][] = 'unique:remote_servers,ip_address,' . $remoteServerId;
        }

        return $request->validate($rules);
    }

    /**
     * Find a remote server by ID.
     */
    private function findRemoteServer(mixed $id): ?RemoteServer
    {
        if (! is_numeric($id)) {
            return null;
        }

        return RemoteServer::find((int) $id);
    }
}
