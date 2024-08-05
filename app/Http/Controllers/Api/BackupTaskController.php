<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BackupTaskResource;
use App\Models\BackupTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class BackupTaskController extends Controller
{
    /**
     * Display a paginated listing of the backup tasks.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('view-backup-tasks')) {
            abort(403, 'Unauthorized action.');
        }

        $perPage = $request->input('per_page', 15);
        $backupTasks = BackupTask::where('user_id', $user->id)->paginate($perPage);

        return BackupTaskResource::collection($backupTasks);
    }

    /**
     * Store a newly created backup task in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('create-backup-tasks')) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['user_id'] = $user->id;

        $backupTask = BackupTask::create($validated);

        return (new BackupTaskResource($backupTask))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified backup task.
     */
    public function show(Request $request, BackupTask $backupTask): BackupTaskResource
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('view-backup-tasks')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('view', $backupTask);

        return new BackupTaskResource($backupTask);
    }

    /**
     * Update the specified backup task in storage.
     */
    public function update(Request $request, BackupTask $backupTask): BackupTaskResource
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('update-backup-tasks')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('update', $backupTask);

        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            abort(422, $validator->errors()->first());
        }

        $validated = $validator->validated();

        $backupTask->update($validated);

        return new BackupTaskResource($backupTask);
    }

    /**
     * Remove the specified backup task from storage.
     */
    public function destroy(Request $request, BackupTask $backupTask): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('delete-backup-tasks')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('forceDelete', $backupTask);

        $backupTask->delete();

        return response()->noContent();
    }

    /**
     * Get the validation rules for backup tasks.
     *
     * @return array<string, mixed>
     */
    private function getValidationRules(): array
    {
        return [
            'remote_server_id' => ['required', 'exists:remote_servers,id'],
            'backup_destination_id' => ['required', 'exists:backup_destinations,id'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'source_path' => ['required_if:type,files', 'nullable', 'string'],
            'frequency' => ['required', 'string', 'in:daily,weekly'],
            'time_to_run_at' => ['required_without:custom_cron_expression', 'nullable', 'date_format:H:i'],
            'custom_cron_expression' => ['required_without:time_to_run_at', 'nullable', 'string'],
            'maximum_backups_to_keep' => ['required', 'integer', 'min:0'],
            'type' => ['required', 'in:files,database'],
            'database_name' => ['required_if:type,database', 'nullable', 'string'],
            'appended_file_name' => ['nullable', 'string'],
            'store_path' => ['nullable', 'string'],
            'excluded_database_tables' => ['nullable', 'string'],
            'isolated_username' => ['nullable', 'string'],
            'isolated_password' => ['nullable', 'string'],
        ];
    }
}
