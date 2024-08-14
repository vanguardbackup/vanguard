<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Sanctum\PersonalAccessToken;

class PersonalAccessTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PersonalAccessToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'token' => hash('sha256', $plainTextToken = $this->faker->unique()->password(40)),
            'abilities' => ['*'],
            'tokenable_id' => User::factory(),
            'tokenable_type' => User::class,
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'last_used_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
            'last_notification_sent_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the token is expired.
     */
    public function expired(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Indicate that the token is expiring soon (within the next 3 days).
     */
    public function expiringSoon(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('now', '+3 days'),
        ]);
    }

    /**
     * Indicate that the token has not been used.
     */
    public function unused(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => null,
        ]);
    }

    /**
     * Indicate that no notification has been sent for this token.
     */
    public function neverNotified(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'last_notification_sent_at' => null,
        ]);
    }

    /**
     * Indicate that a notification was sent recently (within the last day).
     */
    public function recentlyNotified(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'last_notification_sent_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Indicate that a notification was sent a while ago (more than a day ago).
     */
    public function notifiedLongAgo(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'last_notification_sent_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }
}
