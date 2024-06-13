<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RemoteServerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => fake()->word,
            'ip_address' => fake()->ipv4,
            'username' => fake()->userName,
            'port' => '22',
            'last_connected_at' => null,
            'user_id' => User::factory()->create()->id,
            'database_password' => fake()->password,
        ];
    }
}
