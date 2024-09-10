<?php

declare(strict_types=1);

namespace App\Spotlight;

use LivewireUI\Spotlight\Spotlight;
use LivewireUI\Spotlight\SpotlightCommand;

class NavigateToStatistics extends SpotlightCommand
{
    /**
     * The name of the command shown in the Spotlight component.
     */
    protected string $name = 'Go to Statistics';

    /**
     * A brief description of the command's purpose.
     */
    protected string $description = 'Navigate to Statistics.';

    /**
     * Additional search terms to help users find this command.
     *
     * @var array<int, string>
     */
    protected array $synonyms = [
        'charts',
        'data',
        'stats',
        'graphs',
    ];

    /**
     * Execute the command, redirecting the user to the statistics page.
     */
    public function execute(Spotlight $spotlight): void
    {
        $spotlight->redirect(route('statistics'));
    }

    /**
     * Determine if this command should be displayed in the Spotlight component.
     */
    public function shouldBeShown(): bool
    {
        return true;
    }
}
