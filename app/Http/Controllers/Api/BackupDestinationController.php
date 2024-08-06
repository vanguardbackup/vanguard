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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Manages API operations for backup destinations.
 */
class BackupDestinationController extends Controller
{
    /**
     * Display a paginated listing of the backup destinations.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeRequest($request, 'view-backup-destinations');

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $perPage = (int) $request->input('per_page', 15);
        $backupDestinations = BackupDestination::where('user_id', $user->id)
            ->paginate($perPage);

        return BackupDestinationResource::collection($backupDestinations);
    }

    /**
     * Store a newly created backup destination.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeRequest($request, 'create-backup-destinations');

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $validated = $this->validateBackupDestination($request);
        $backupDestination = BackupDestination::create($validated + ['user_id' => $user->id]);

        return (new BackupDestinationResource($backupDestination))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified backup destination.
     */
    public function show(Request $request, mixed $id): BackupDestinationResource|JsonResponse
    {
        $this->authorizeRequest($request, 'view-backup-destinations');

        $backupDestination = $this->findBackupDestination($id);

        if (! $backupDestination instanceof BackupDestination) {
            return response()->json(['message' => 'Backup destination not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('view', $backupDestination);

        return new BackupDestinationResource($backupDestination);
    }

    /**
     * Update the specified backup destination.
     */
    public function update(Request $request, mixed $id): BackupDestinationResource|JsonResponse
    {
        $this->authorizeRequest($request, 'update-backup-destinations');

        $backupDestination = $this->findBackupDestination($id);

        if (! $backupDestination instanceof BackupDestination) {
            return response()->json(['message' => 'Backup destination not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('update', $backupDestination);

        $validated = $this->validateBackupDestination($request, true);
        $backupDestination->update($validated);

        return new BackupDestinationResource($backupDestination);
    }

    /**
     * Remove the specified backup destination.
     */
    public function destroy(Request $request, mixed $id): Response|JsonResponse
    {
        $this->authorizeRequest($request, 'delete-backup-destinations');

        $backupDestination = $this->findBackupDestination($id);

        if (! $backupDestination instanceof BackupDestination) {
            return response()->json(['message' => 'Backup destination not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('forceDelete', $backupDestination);

        $backupDestination->delete();

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
     * Find a backup destination by ID.
     */
    private function findBackupDestination(mixed $id): ?BackupDestination
    {
        if (! is_numeric($id)) {
            return null;
        }

        return BackupDestination::find((int) $id);
    }

    /**
     * Validate the backup destination data.
     *
     * @return array<string, mixed>
     */
    private function validateBackupDestination(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:custom_s3,s3,local'],
            's3_access_key' => ['nullable', 'required_if:type,custom_s3,s3', 'string', 'max:255'],
            's3_secret_key' => ['nullable', 'required_if:type,custom_s3,s3', 'string', 'max:255'],
            's3_bucket_name' => ['nullable', 'required_if:type,custom_s3,s3', 'string', 'max:255'],
            'custom_s3_region' => ['nullable', 'required_if:type,s3', 'string', 'max:255'],
            'custom_s3_endpoint' => ['nullable', 'required_if:type,custom_s3', 'string', 'max:255'],
            'path_style_endpoint' => ['boolean', 'required_if:type,s3,custom_s3'],
        ];

        if ($isUpdate) {
            $rules = array_map(fn (array $rule): array => array_merge(['sometimes'], $rule), $rules);
        }

        return $request->validate($rules);
    }
}
