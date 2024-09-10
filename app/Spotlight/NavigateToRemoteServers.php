<?php

declare(strict_types=1);

namespace App\Spotlight;

use LivewireUI\Spotlight\Spotlight;
use LivewireUI\Spotlight\SpotlightCommand;

class NavigateToRemoteServers extends SpotlightCommand
{
    /**
     * The name of the command shown in the Spotlight component.
     */
    protected string $name = 'Go to Remote Servers';

    /**
     * A brief description of the command's purpose.
     */
    protected string $description = 'Navigate to the remote servers page.';

    /**
     * Additional search terms to help users find this command.
     *
     * @var array<int, string>
     */
    protected array $synonyms = [
        'servers',
        'remote servers',
    ];

    /**
     * Execute the command, redirecting the user to the remote servers page.
     */
    public function execute(Spotlight $spotlight): void
    {
        $spotlight->redirect(route('remote-servers.index'));
    }

    /**
     * Determine if this command should be displayed in the Spotlight component.
     */
    public function shouldBeShown(): bool
    {
        return true;
    }
}
