<?php

namespace Database\Factories;

use App\Models\BackupTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupTaskLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'backup_task_id' => BackupTask::factory()->create()->id,
            'output' => fake()->sentence(5),
        ];
    }
}
