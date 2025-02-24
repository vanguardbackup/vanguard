<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class BackupTaskLogResource extends JsonResource
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
            'backup_task_id' => $this->resource->backup_task_id,
            'output' => $this->resource->output,
            'finished_at' => $this->resource->finished_at,
            'status' => $this->resource->successful_at ? 'successful' : 'failed',
            'created_at' => $this->resource->created_at,
        ];
    }
}
