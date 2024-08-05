<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationStreamResource;
use App\Models\NotificationStream;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class NotificationStreamController extends Controller
{
    /**
     * Display a paginated listing of the notification streams.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('view-notification-streams')) {
            abort(403, 'Unauthorized action.');
        }

        $perPage = $request->input('per_page', 15);
        $notificationStreams = NotificationStream::where('user_id', $user->id)->paginate($perPage);

        return NotificationStreamResource::collection($notificationStreams);
    }

    /**
     * Store a newly created notification stream in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('create-notification-streams')) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['user_id'] = $user->id;

        $validated['receive_successful_backup_notifications'] = $validated['receive_successful_backup_notifications'] ? now() : null;
        $validated['receive_failed_backup_notifications'] = $validated['receive_failed_backup_notifications'] ? now() : null;

        $notificationStream = NotificationStream::create($validated);

        return (new NotificationStreamResource($notificationStream))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified notification stream.
     */
    public function show(Request $request, NotificationStream $notificationStream): NotificationStreamResource
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('view-notification-streams')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('view', $notificationStream);

        return new NotificationStreamResource($notificationStream);
    }

    /**
     * Update the specified notification stream in storage.
     */
    public function update(Request $request, NotificationStream $notificationStream): NotificationStreamResource
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('update-notification-streams')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('update', $notificationStream);

        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            abort(422, $validator->errors()->first());
        }

        $validated = $validator->validated();

        $validated['receive_successful_backup_notifications'] = $validated['receive_successful_backup_notifications'] ? now() : null;
        $validated['receive_failed_backup_notifications'] = $validated['receive_failed_backup_notifications'] ? now() : null;

        $notificationStream->update($validated);

        return new NotificationStreamResource($notificationStream);
    }

    /**
     * Remove the specified notification stream from storage.
     */
    public function destroy(Request $request, NotificationStream $notificationStream): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('delete-notification-streams')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('forceDelete', $notificationStream);

        $notificationStream->delete();

        return response()->noContent();
    }

    /**
     * Get the validation rules for notification streams.
     *
     * @return array<string, mixed>
     */
    private function getValidationRules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:' . implode(',', [
                NotificationStream::TYPE_EMAIL,
                NotificationStream::TYPE_DISCORD,
                NotificationStream::TYPE_SLACK,
                NotificationStream::TYPE_TEAMS,
                NotificationStream::TYPE_PUSHOVER,
            ])],
            'value' => ['required', 'string'],
            'receive_successful_backup_notifications' => ['nullable', 'boolean'],
            'receive_failed_backup_notifications' => ['nullable', 'boolean'],
            'additional_field_one' => ['nullable', 'string'],
            'additional_field_two' => ['nullable', 'string'],
        ];
    }
}
