<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Override;

class ScheduledBackupTaskResource extends JsonResource
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
            'backup_task_id' => $this->resource->task->getKey(),
            'label' => $this->resource->task->label,
            'type' => $this->resource->type,
            'next_run' => $this->resource->next_run instanceof Carbon
                ? $this->resource->next_run->toIso8601String()
                : null,
            'next_run_human' => $this->resource->due_to_run,
        ];
    }
}
