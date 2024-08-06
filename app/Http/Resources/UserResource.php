<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource class for transforming User model data into a JSON response.
 *
 * This class provides a structured way to convert User model attributes
 * and related data into an array suitable for API responses.
 *
 * @property-read User $resource The underlying User model instance.
 */
class UserResource extends JsonResource
{
    /**
     * Transform the User resource into an array.
     *
     * This method converts the User model and its related data into a structured array
     * suitable for API responses. It includes user details, account settings, and various statistics.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return array<string, mixed> An array representation of the User resource.
     *
     * The returned array includes the following structure:
     * - id: The user's unique identifier.
     * - personal_info: Object containing the user's personal information.
     * - account_settings: Object containing the user's account settings.
     * - backup_tasks: Object containing backup task statistics.
     * - related_entities: Object containing counts of related entities.
     * - timestamps: Object containing important account-related dates.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getAttribute('id'),
            'personal_info' => [
                'name' => $this->resource->getAttribute('name'),
                'first_name' => $this->resource->getAttribute('firstName'),
                'last_name' => $this->resource->getAttribute('lastName'),
                'email' => $this->resource->getAttribute('email'),
                'avatar_url' => $this->resource->gravatar(200),
            ],
            'account_settings' => [
                'timezone' => $this->resource->getAttribute('timezone'),
                'language' => $this->resource->getAttribute('language'),
                'is_admin' => $this->resource->isAdmin(),
                'github_login_enabled' => $this->resource->canLoginWithGithub(),
                'weekly_summary_enabled' => $this->resource->isOptedInForWeeklySummary(),
            ],
            'backup_tasks' => [
                'total' => $this->resource->backupTasks()->count(),
                'active' => $this->resource->backupTasks()->where('status', 'ready')->count(),
                'logs' => [
                    'total' => $this->resource->backupTaskLogCount(),
                    'today' => $this->resource->backupTasklogCountToday(),
                ],
            ],
            'related_entities' => [
                'remote_servers' => $this->resource->remoteServers()->count(),
                'backup_destinations' => $this->resource->backupDestinations()->count(),
                'tags' => $this->resource->tags()->count(),
                'notification_streams' => $this->resource->notificationStreams()->count(),
            ],
            'timestamps' => [
                'account_created' => $this->resource->getAttribute('created_at')?->toIso8601String(),
            ],
        ];
    }
}
