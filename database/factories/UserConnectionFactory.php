<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Database\Eloquent\Factories\Factory;
use JsonException;

/**
 * @extends Factory<UserConnection>
 */
class UserConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_name' => $this->faker->randomElement([
                UserConnection::PROVIDER_GITHUB,
                UserConnection::PROVIDER_GITLAB,
                UserConnection::PROVIDER_BITBUCKET,
            ]),
            'provider_user_id' => $this->faker->uuid,
            'provider_email' => $this->faker->safeEmail,
            'access_token' => $this->faker->sha256,
            'refresh_token' => $this->faker->sha256,
            'token_expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'scopes' => json_encode(['read:user', 'repo'], JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * Indicate that the connection is for GitHub.
     */
    public function github(): self
    {
        return $this->state(fn (array $attributes) => [
            'provider_name' => UserConnection::PROVIDER_GITHUB,
        ]);
    }

    /**
     * Indicate that the connection is for GitLab.
     */
    public function gitlab(): self
    {
        return $this->state(fn (array $attributes) => [
            'provider_name' => UserConnection::PROVIDER_GITLAB,
        ]);
    }

    /**
     * Indicate that the connection is for Bitbucket.
     */
    public function bitbucket(): self
    {
        return $this->state(fn (array $attributes) => [
            'provider_name' => UserConnection::PROVIDER_BITBUCKET,
        ]);
    }
}
