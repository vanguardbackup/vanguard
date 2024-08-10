<?php

namespace Database\Factories;

use App\Models\ApiUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiUsageFactory extends Factory
{
    protected $model = ApiUsage::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'endpoint' => $this->faker->url,
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']),
            'response_status' => $this->faker->numberBetween(200, 500),
            'response_time_ms' => $this->faker->numberBetween(50, 5000),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ];
    }
}
