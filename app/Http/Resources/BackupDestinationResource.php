<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackupDestinationResource extends JsonResource
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
            'type' => $this->resource->type,
            's3_bucket_name' => $this->when($this->resource->type !== 'local', $this->resource->s3_bucket_name),
            'custom_s3_region' => $this->when($this->resource->type === 's3', $this->resource->custom_s3_region),
            'custom_s3_endpoint' => $this->when($this->resource->type === 'custom_s3', $this->resource->custom_s3_endpoint),
            'path_style_endpoint' => $this->when($this->resource->type !== 'local', $this->resource->path_style_endpoint),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
