<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class BackupTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'remote_server_id' => $this->resource->remote_server_id,
            'backup_destination_id' => $this->resource->backup_destination_id,
            'label' => $this->resource->label,
            'description' => $this->resource->description,
            'source' => [
                'path' => $this->resource->source_path,
                'type' => $this->resource->type,
                'database_name' => $this->resource->database_name ?? null,
                'excluded_tables' => $this->resource->excluded_database_tables ?? null,
            ],
            'schedule' => [
                'frequency' => $this->resource->frequency,
                'scheduled_utc_time' => $this->resource->time_to_run_at,
                'scheduled_local_time' => $this->resource->runTimeFormatted(),
                'custom_cron' => $this->resource->custom_cron_expression,
            ],
            'storage' => [
                'max_backups' => $this->resource->maximum_backups_to_keep,
                'appended_filename' => $this->resource->appended_filename ?? null,
                'path' => $this->resource->store_path,
            ],
            'notification_streams_count' => $this->resource->notificationStreams()->count ?? 0,
            'status' => $this->resource->status,
            'has_encryption_password' => ! is_null($this->resource->encryption_password),
            'last_run_local_time' => $this->resource->lastRunFormatted(),
            'last_run_utc_time' => $this->resource->last_run_at,
            'paused_at' => $this->resource->paused_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
