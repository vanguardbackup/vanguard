<?php

namespace Database\Factories;

use App\Models\NotificationStream;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationStreamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement([
            NotificationStream::TYPE_EMAIL,
            NotificationStream::TYPE_SLACK,
            NotificationStream::TYPE_DISCORD,
        ]);

        return [
            'label' => $this->faker->word,
            'type' => $type,
            'value' => $this->getValueForType($type),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the notification stream is for email.
     */
    public function email(): static
    {
        return $this->state([
            'type' => NotificationStream::TYPE_EMAIL,
            'value' => $this->faker->safeEmail,
        ]);
    }

    /**
     * Indicate that the notification stream is for Slack.
     */
    public function slack(): static
    {
        return $this->state([
            'type' => NotificationStream::TYPE_SLACK,
            'value' => $this->generateSlackWebhook(),
        ]);
    }

    /**
     * Indicate that the notification stream is for Discord.
     */
    public function discord(): static
    {
        return $this->state([
            'type' => NotificationStream::TYPE_DISCORD,
            'value' => $this->generateDiscordWebhook(),
        ]);
    }

    /**
     * Get the appropriate value for the given notification type.
     */
    private function getValueForType(string $type): string
    {
        return match ($type) {
            NotificationStream::TYPE_EMAIL => $this->faker->safeEmail,
            NotificationStream::TYPE_SLACK => $this->generateSlackWebhook(),
            NotificationStream::TYPE_DISCORD => $this->generateDiscordWebhook(),
            default => $this->faker->url,
        };
    }

    /**
     * Generate a realistic Slack webhook URL.
     */
    private function generateSlackWebhook(): string
    {
        $workspace = $this->faker->regexify('[A-Z0-9]{9}');
        $channel = $this->faker->regexify('[A-Z0-9]{9}');
        $token = $this->faker->regexify('[a-zA-Z0-9]{24}');

        return "https://hooks.slack.com/services/{$workspace}/{$channel}/{$token}";
    }

    /**
     * Generate a realistic Discord webhook URL.
     */
    private function generateDiscordWebhook(): string
    {
        $id = $this->faker->numberBetween(100000000000000000, 999999999999999999);
        $token = $this->faker->regexify('[a-zA-Z0-9_-]{68}');

        return "https://discord.com/api/webhooks/{$id}/{$token}";
    }
}
