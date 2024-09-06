<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDismissal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserDismissal>
 */
class UserDismissalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserDismissal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'dismissable_type' => $this->faker->randomElement(['feature']),
            'dismissable_id' => $this->faker->word(),
            'dismissed_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the dismissal is for a feature.
     */
    public function feature(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'dismissable_type' => 'feature',
                'dismissable_id' => $this->faker->randomElement(['new_backup_system', 'dark_mode', 'api_integration']),
            ];
        });
    }
}
