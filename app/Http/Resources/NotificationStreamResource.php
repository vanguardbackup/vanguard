<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationStreamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'label' => $this->resource->label,
            'type' => $this->resource->type,
            'value' => $this->resource->value,
            'receive_successful_backup_notifications' => (bool) $this->resource->receive_successful_backup_notifications,
            'receive_failed_backup_notifications' => (bool) $this->resource->receive_failed_backup_notifications,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        // Only include additional fields if they are set
        if ($this->resource->additional_field_one !== null) {
            $data['additional_field_one'] = $this->resource->additional_field_one;
        }

        if ($this->resource->additional_field_two !== null) {
            $data['additional_field_two'] = $this->resource->additional_field_two;
        }

        return $data;
    }
}
