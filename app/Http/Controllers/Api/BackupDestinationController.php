<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BackupDestinationResource;
use App\Models\BackupDestination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BackupDestinationController extends Controller
{
    /**
     * Display a paginated listing of the backup destinations.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('view-backup-destinations')) {
            abort(403, 'Unauthorized action.');
        }

        $perPage = $request->input('per_page', 15); // Default to 15 items per page
        $backupDestinations = BackupDestination::where('user_id', $user->id)
            ->paginate($perPage);

        return BackupDestinationResource::collection($backupDestinations);
    }

    /**
     * Store a newly created backup destination.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('create-backup-destinations')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'label' => ['required', 'string'],
            'type' => ['required', 'string', 'in:custom_s3,s3,local'],
            's3_access_key' => ['nullable', 'required_if:type,custom_s3,s3'],
            's3_secret_key' => ['nullable', 'required_if:type,custom_s3,s3'],
            's3_bucket_name' => ['nullable', 'required_if:type,custom_s3,s3'],
            'custom_s3_region' => ['nullable', 'required_if:type,s3'],
            'custom_s3_endpoint' => ['nullable', 'required_if:type,custom_s3'],
            'path_style_endpoint' => ['boolean', 'required_if:type,s3,custom_s3'],
        ]);

        $backupDestination = BackupDestination::create($validated + ['user_id' => $user->id]);

        return (new BackupDestinationResource($backupDestination))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified backup destination.
     */
    public function show(Request $request, BackupDestination $backupDestination): BackupDestinationResource
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('view-backup-destinations')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('view', $backupDestination);

        return new BackupDestinationResource($backupDestination);
    }

    /**
     * Update the specified backup destination.
     */
    public function update(Request $request, BackupDestination $backupDestination): BackupDestinationResource
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('update-backup-destinations')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('update', $backupDestination);

        $validated = $request->validate([
            'label' => ['sometimes', 'required', 'string'],
            'type' => ['sometimes', 'required', 'string', 'in:custom_s3,s3,local'],
            's3_access_key' => ['nullable', 'required_if:type,custom_s3,s3'],
            's3_secret_key' => ['nullable', 'required_if:type,custom_s3,s3'],
            's3_bucket_name' => ['nullable', 'required_if:type,custom_s3,s3'],
            'custom_s3_region' => ['nullable', 'required_if:type,s3'],
            'custom_s3_endpoint' => ['nullable', 'required_if:type,custom_s3'],
            'path_style_endpoint' => ['boolean', 'required_if:type,s3,custom_s3'],
        ]);

        $backupDestination->update($validated);

        return new BackupDestinationResource($backupDestination);
    }

    /**
     * Remove the specified backup destination.
     */
    public function destroy(Request $request, BackupDestination $backupDestination): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('delete-backup-destinations')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('forceDelete', $backupDestination);

        $backupDestination->delete();

        return response()->noContent();
    }
}
