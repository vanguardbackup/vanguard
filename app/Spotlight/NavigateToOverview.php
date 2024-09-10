<?php

declare(strict_types=1);

namespace App\Spotlight;

use LivewireUI\Spotlight\Spotlight;
use LivewireUI\Spotlight\SpotlightCommand;

class NavigateToOverview extends SpotlightCommand
{
    /**
     * The name of the command shown in the Spotlight component.
     */
    protected string $name = 'Go to Overview';

    /**
     * A brief description of the command's purpose.
     */
    protected string $description = 'Navigate to the overview page.';

    /**
     * Additional search terms to help users find this command.
     *
     * @var array<int, string>
     */
    protected array $synonyms = [
        'overview',
        'dashboard',
        'home',
    ];

    /**
     * Execute the command, redirecting the user to the overview page.
     */
    public function execute(Spotlight $spotlight): void
    {
        $spotlight->redirect(route('overview'));
    }

    /**
     * Determine if this command should be displayed in the Spotlight component.
     */
    public function shouldBeShown(): bool
    {
        return true;
    }
}
