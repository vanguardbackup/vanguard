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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Manages API operations for notification streams.
 */
class NotificationStreamController extends Controller
{
    /**
     * Display a paginated listing of the notification streams.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeRequest($request, 'view-notification-streams');

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $perPage = (int) $request->input('per_page', 15);
        $notificationStreams = NotificationStream::where('user_id', $user->id)->paginate($perPage);

        return NotificationStreamResource::collection($notificationStreams);
    }

    /**
     * Store a newly created notification stream in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeRequest($request, 'create-notification-streams');

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        try {
            $validated = $this->validateNotificationStream($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

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
    public function show(Request $request, mixed $id): NotificationStreamResource|JsonResponse
    {
        $this->authorizeRequest($request, 'view-notification-streams');

        $notificationStream = $this->findNotificationStream($id);

        if (! $notificationStream instanceof NotificationStream) {
            return response()->json(['message' => 'Notification stream not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('view', $notificationStream);

        return new NotificationStreamResource($notificationStream);
    }

    /**
     * Update the specified notification stream in storage.
     */
    public function update(Request $request, mixed $id): NotificationStreamResource|JsonResponse
    {
        $this->authorizeRequest($request, 'update-notification-streams');

        $notificationStream = $this->findNotificationStream($id);

        if (! $notificationStream instanceof NotificationStream) {
            return response()->json(['message' => 'Notification stream not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('update', $notificationStream);

        try {
            $validated = $this->validateNotificationStream($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated['receive_successful_backup_notifications'] = $validated['receive_successful_backup_notifications'] ? now() : null;
        $validated['receive_failed_backup_notifications'] = $validated['receive_failed_backup_notifications'] ? now() : null;

        $notificationStream->update($validated);

        return new NotificationStreamResource($notificationStream);
    }

    /**
     * Remove the specified notification stream from storage.
     */
    public function destroy(Request $request, mixed $id): Response|JsonResponse
    {
        $this->authorizeRequest($request, 'delete-notification-streams');

        $notificationStream = $this->findNotificationStream($id);

        if (! $notificationStream instanceof NotificationStream) {
            return response()->json(['message' => 'Notification stream not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('forceDelete', $notificationStream);

        $notificationStream->delete();

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
     * Validate the notification stream data.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validateNotificationStream(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:' . implode(',', [
                NotificationStream::TYPE_EMAIL,
                NotificationStream::TYPE_DISCORD,
                NotificationStream::TYPE_SLACK,
                NotificationStream::TYPE_TEAMS,
                NotificationStream::TYPE_PUSHOVER,
            ])],
            'value' => ['required', 'string', 'max:255'],
            'receive_successful_backup_notifications' => ['nullable', 'boolean'],
            'receive_failed_backup_notifications' => ['nullable', 'boolean'],
            'additional_field_one' => ['nullable', 'string', 'max:255'],
            'additional_field_two' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * Find a notification stream by ID.
     */
    private function findNotificationStream(mixed $id): ?NotificationStream
    {
        if (! is_numeric($id)) {
            return null;
        }

        return NotificationStream::find((int) $id);
    }
}
