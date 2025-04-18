<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<UserSuspension>
 */
class UserSuspensionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $suspendedAt = now()->subDays(fake()->numberBetween(1, 30));
        $suspendedUntil = fake()->randomElement([
            $suspendedAt->copy()->addDays(fake()->numberBetween(1, 60)),
            $suspendedAt->copy()->addMonths(fake()->numberBetween(1, 6)),
            null, // Permanent suspension
        ]);

        $suspensionReasons = [
            'Spamming',
            'Suspicious Account Activity',
            'Bot',
            'Violation of Terms of Service',
            'Harassment',
            'Hate Speech',
            'Inappropriate Content',
            'Impersonation',
            'Fraudulent Activity',
            'Multiple Account Abuse',
            'Threats or Violence',
            'Unauthorized Data Collection',
        ];

        return [
            'user_id' => User::factory()->create()->id,
            'admin_user_id' => User::factory()->create()->id,
            'suspended_at' => $suspendedAt,
            'suspended_until' => $suspendedUntil,
            'suspended_reason' => Arr::random($suspensionReasons),
            'private_note' => fake()->boolean(70) ? fake()->sentence() : null,
            'notify_user_upon_suspension_being_lifted_at' => $suspendedUntil ? fake()->boolean(50) ? $suspendedUntil : null : null,
            'created_at' => $suspendedAt,
            'updated_at' => $suspendedAt,
        ];
    }

    /**
     * Create an active suspension
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $suspendedAt = now()->subDays(fake()->numberBetween(1, 5));

            return [
                'suspended_at' => $suspendedAt,
                'suspended_until' => now()->addDays(fake()->numberBetween(1, 30)),
                'created_at' => $suspendedAt,
                'updated_at' => $suspendedAt,
            ];
        });
    }

    /**
     * Create an expired suspension
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $suspendedAt = now()->subDays(fake()->numberBetween(30, 90));

            return [
                'suspended_at' => $suspendedAt,
                'suspended_until' => now()->subDays(fake()->numberBetween(1, 29)),
                'created_at' => $suspendedAt,
                'updated_at' => $suspendedAt,
            ];
        });
    }

    /**
     * Create a permanent suspension
     */
    public function permanent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'suspended_until' => null,
            ];
        });
    }
}
