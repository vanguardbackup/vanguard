<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupDestinationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create()->id,
            'label' => fake()->word,
            'type' => 'custom_s3',
            's3_access_key' => fake()->word,
            's3_secret_key' => fake()->word,
            's3_bucket_name' => fake()->word,
            'custom_s3_region' => fake()->word,
            'custom_s3_endpoint' => fake()->url,
            'status' => 'unknown',
            'path_style_endpoint' => false,
        ];
    }

    public function reachable(): self
    {
        return $this->state([
            'status' => 'reachable',
        ]);
    }

    public function unreachable(): self
    {
        return $this->state([
            'status' => 'unreachable',
        ]);
    }

    public function unknown(): self
    {
        return $this->state([
            'status' => 'unknown',
        ]);
    }

    public function checking(): self
    {
        return $this->state([
            'status' => 'checking',
        ]);
    }
}
