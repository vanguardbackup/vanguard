<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'timezone' => 'UTC',
            'pagination_count' => '25',
            'language' => 'en',
            'weekly_summary_opt_in_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function receivesWeeklySummaries(): static
    {
        return $this->state(fn (array $attributes) => [
            'weekly_summary_opt_in_at' => now(),
        ]);
    }

    public function doesNotReceiveWeeklySummaries(): static
    {
        return $this->state(fn (array $attributes) => [
            'weekly_summary_opt_in_at' => null,
        ]);
    }
}
