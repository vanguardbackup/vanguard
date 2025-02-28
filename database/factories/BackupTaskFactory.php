<?php

namespace Database\Factories;

use App\Models\BackupDestination;
use App\Models\RemoteServer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupTaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => fake()->sentence,
            'description' => fake()->paragraph,
            'source_path' => fake()->word,
            'frequency' => 'daily',
            'status' => 'ready',
            'custom_cron_expression' => null,
            'user_id' => User::factory()->create()->id,
            'backup_destination_id' => BackupDestination::factory()->create()->id,
            'remote_server_id' => RemoteServer::factory()->create()->id,
            'type' => 'files',
            'maximum_backups_to_keep' => 5,
            'run_webhook_last_used_at' => null,
        ];
    }

    public function paused(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'paused_at' => now(),
            ];
        });
    }

    public function favourited(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'favourited_at' => now(),
            ];
        });
    }
}
