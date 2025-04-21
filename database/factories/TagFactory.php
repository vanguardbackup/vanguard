<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'colour' => fake()->hexColor(),
            'user_id' => User::factory()->create()->id,
        ];
    }
}
