<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemoteServerResource extends JsonResource
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
            'label' => $this->resource->label,
            'connection' => [
                'ip_address' => $this->resource->ip_address,
                'username' => $this->resource->username,
                'port' => $this->resource->port,
                'is_database_password_set' => ! is_null($this->resource->database_password),
            ],
            'status' => [
                'connectivity' => $this->resource->connectivity_status,
                'last_connected_at' => $this->resource->last_connected_at,
            ],
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
