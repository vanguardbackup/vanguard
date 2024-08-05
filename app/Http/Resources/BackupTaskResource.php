<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackupTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'remote_server_id' => $this->resource->remote_server_id,
            'backup_destination_id' => $this->resource->backup_destination_id,
            'label' => $this->resource->label,
            'description' => $this->resource->description,
            'source_path' => $this->resource->source_path,
            'frequency' => $this->resource->frequency,
            'time_to_run_at' => $this->resource->time_to_run_at,
            'custom_cron_expression' => $this->resource->custom_cron_expression,
            'status' => $this->resource->status,
            'maximum_backups_to_keep' => $this->resource->maximum_backups_to_keep,
            'type' => $this->resource->type,
            'database_name' => $this->resource->database_name,
            'appended_file_name' => $this->resource->appended_file_name,
            'store_path' => $this->resource->store_path,
            'excluded_database_tables' => $this->resource->excluded_database_tables,
            'has_isolated_credentials' => ! is_null($this->resource->isolated_username) && ! is_null($this->resource->isolated_password),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
