<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationStreamResource;
use App\Models\NotificationStream;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
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
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $perPage = (int) $request->input('per_page', 15);
        $notificationStreams = NotificationStream::where('user_id', $user->getAttribute('id'))->paginate($perPage);

        return NotificationStreamResource::collection($notificationStreams);
    }

    /**
     * Store a newly created notification stream in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateNotificationStream($request);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var User $user */
        $user = $request->user();

        $validated['user_id'] = $user->getAttribute('id');

        // Process the nested notifications data
        $validated['receive_successful_backup_notifications'] = $validated['notifications']['on_success'] ? now() : null;
        $validated['receive_failed_backup_notifications'] = $validated['notifications']['on_failure'] ? now() : null;

        // Remove the notifications key as it's not a direct field in the model
        unset($validated['notifications']);

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
        $notificationStream = $this->findNotificationStream($id);

        if (! $notificationStream instanceof NotificationStream) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Notification stream not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (Gate::denies('view', $notificationStream)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not authorized to view this notification stream',
            ], Response::HTTP_FORBIDDEN);
        }

        return new NotificationStreamResource($notificationStream);
    }

    /**
     * Update the specified notification stream in storage.
     */
    public function update(Request $request, mixed $id): NotificationStreamResource|JsonResponse
    {
        $notificationStream = $this->findNotificationStream($id);

        if (! $notificationStream instanceof NotificationStream) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Notification stream not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (Gate::denies('update', $notificationStream)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not authorized to update this notification stream',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $validated = $this->validateNotificationStream($request);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Process the nested notifications data
        $validated['receive_successful_backup_notifications'] = $validated['notifications']['on_success'] ? now() : null;
        $validated['receive_failed_backup_notifications'] = $validated['notifications']['on_failure'] ? now() : null;

        // Remove the notifications key as it's not a direct field in the model
        unset($validated['notifications']);

        $notificationStream->update($validated);

        return new NotificationStreamResource($notificationStream);
    }

    /**
     * Remove the specified notification stream from storage.
     */
    public function destroy(Request $request, mixed $id): Response|JsonResponse
    {
        $notificationStream = $this->findNotificationStream($id);

        if (! $notificationStream instanceof NotificationStream) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Notification stream not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (Gate::denies('forceDelete', $notificationStream)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not authorized to delete this notification stream',
            ], Response::HTTP_FORBIDDEN);
        }

        $notificationStream->delete();

        return response()->noContent();
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
            'notifications' => ['required', 'array'],
            'notifications.on_success' => ['required', 'boolean'],
            'notifications.on_failure' => ['required', 'boolean'],
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
