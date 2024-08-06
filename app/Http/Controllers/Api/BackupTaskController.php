<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BackupTaskResource;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\RemoteServer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Manages API operations for backup tasks.
 */
class BackupTaskController extends Controller
{
    /**
     * Display a paginated listing of the backup tasks.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $authResponse = $this->authorizeRequest($request, 'view-backup-tasks');

        if ($authResponse instanceof JsonResponse) {
            return $authResponse;
        }

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $perPage = (int) $request->input('per_page', 15);
        $backupTasks = BackupTask::where('user_id', $user->id)->paginate($perPage);

        return BackupTaskResource::collection($backupTasks);
    }

    /**
     * Store a newly created backup task in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $authResponse = $this->authorizeRequest($request, 'create-backup-tasks');

        if ($authResponse instanceof JsonResponse) {
            return $authResponse;
        }

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        try {
            $validated = $this->validateBackupTask($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $errors = $this->validateUserOwnership($validated, $user);

        if ($errors !== []) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated['user_id'] = $user->id;
        $validated['status'] = BackupTask::STATUS_READY;

        $backupTask = BackupTask::create($validated);

        return (new BackupTaskResource($backupTask))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified backup task.
     */
    public function show(Request $request, mixed $id): BackupTaskResource|JsonResponse
    {
        $authResponse = $this->authorizeRequest($request, 'view-backup-tasks');

        if ($authResponse instanceof JsonResponse) {
            return $authResponse;
        }

        $backupTask = $this->findBackupTask($id);

        if (! $backupTask instanceof BackupTask) {
            return response()->json(['message' => 'Backup task not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('view', $backupTask);

        return new BackupTaskResource($backupTask);
    }

    /**
     * Update the specified backup task in storage.
     */
    public function update(Request $request, mixed $id): BackupTaskResource|JsonResponse
    {
        $authResponse = $this->authorizeRequest($request, 'update-backup-tasks');

        if ($authResponse instanceof JsonResponse) {
            return $authResponse;
        }

        $backupTask = $this->findBackupTask($id);

        if (! $backupTask instanceof BackupTask) {
            return response()->json(['message' => 'Backup task not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('update', $backupTask);

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        try {
            $validated = $this->validateBackupTask($request, true);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $errors = $this->validateUserOwnership($validated, $user);

        if ($errors !== []) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $backupTask->update($validated);

        return new BackupTaskResource($backupTask);
    }

    /**
     * Remove the specified backup task from storage.
     */
    public function destroy(Request $request, mixed $id): Response|JsonResponse
    {
        $authResponse = $this->authorizeRequest($request, 'delete-backup-tasks');

        if ($authResponse instanceof JsonResponse) {
            return $authResponse;
        }

        $backupTask = $this->findBackupTask($id);

        if (! $backupTask instanceof BackupTask) {
            return response()->json(['message' => 'Backup task not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('forceDelete', $backupTask);

        $backupTask->delete();

        return response()->noContent();
    }

    /**
     * Validate user ownership of remote server and backup destination.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string[]>
     */
    private function validateUserOwnership(array $data, User $user): array
    {
        $errors = [];

        if (isset($data['remote_server_id'])) {
            $remoteServer = RemoteServer::where('id', $data['remote_server_id'])
                ->where('user_id', $user->getAttribute('id'))
                ->first();
            if (! $remoteServer) {
                $errors['remote_server_id'] = ['The selected remote server is invalid.'];
            }
        }

        if (isset($data['backup_destination_id'])) {
            $backupDestination = BackupDestination::where('id', $data['backup_destination_id'])
                ->where('user_id', $user->getAttribute('id'))
                ->first();
            if (! $backupDestination) {
                $errors['backup_destination_id'] = ['The selected backup destination is invalid.'];
            }
        }

        return $errors;
    }

    /**
     * Validate the backup task data.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validateBackupTask(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'remote_server_id' => ['required', 'integer'],
            'backup_destination_id' => ['required', 'integer'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'source_path' => ['required_if:type,files', 'nullable', 'string'],
            'frequency' => ['required', 'string', 'in:daily,weekly'],
            'maximum_backups_to_keep' => ['required', 'integer', 'min:0'],
            'type' => ['required', 'in:files,database'],
            'database_name' => ['required_if:type,database', 'nullable', 'string'],
            'appended_file_name' => ['nullable', 'string'],
            'store_path' => ['nullable', 'string'],
            'excluded_database_tables' => ['nullable', 'string'],
            'isolated_username' => ['nullable', 'string'],
            'isolated_password' => ['nullable', 'string'],
            'time_to_run_at' => ['required_without:custom_cron_expression', 'nullable', 'date_format:H:i'],
            'custom_cron_expression' => ['required_without:time_to_run_at', 'nullable', 'string'],
        ];

        if ($isUpdate) {
            $rules = array_map(fn (array $rule): array => array_merge(['sometimes'], $rule), $rules);
        }

        return $request->validate($rules);
    }

    /**
     * Find a backup task by ID.
     */
    private function findBackupTask(mixed $id): ?BackupTask
    {
        if (! is_numeric($id)) {
            return null;
        }

        return BackupTask::find((int) $id);
    }
}
