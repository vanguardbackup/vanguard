<?php

namespace Database\Factories;

use App\Models\Script;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Script>
 */
class ScriptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create()->id,
            'label' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement([
                Script::TYPE_PRESCRIPT,
                Script::TYPE_POSTSCRIPT,
            ]),
            'script' => $this->faker->randomElement([
                'echo "Running backup preparation..."',
                'mkdir -p /tmp/backup',
                'find /var/www -name "*.log" -delete',
                'mysqldump --add-drop-table --no-data > schema.sql',
                'cd /var/www && tar -czf /tmp/files_backup.tar.gz public/uploads',
            ]),
        ];
    }

    /**
     * Configure the model factory to create prescript type scripts.
     *
     * @return $this
     */
    public function prescript(): self
    {
        return $this->state(function () {
            return [
                'type' => Script::TYPE_PRESCRIPT,
            ];
        });
    }

    /**
     * Configure the model factory to create postscript type scripts.
     *
     * @return $this
     */
    public function postscript(): self
    {
        return $this->state(function () {
            return [
                'type' => Script::TYPE_POSTSCRIPT,
            ];
        });
    }
}
