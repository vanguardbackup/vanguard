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
        $data = [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'label' => $this->resource->label,
            'type' => $this->resource->type,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        if ($this->resource->type !== 'local') {
            $data['s3_bucket_name'] = $this->resource->s3_bucket_name;
            $data['path_style_endpoint'] = $this->resource->path_style_endpoint;

            if ($this->resource->type === 's3') {
                $data['s3_region'] = $this->resource->custom_s3_region;
            } elseif ($this->resource->type === 'custom_s3') {
                $data['s3_endpoint'] = $this->resource->custom_s3_endpoint;
            }
        }

        return $data;
    }
}
