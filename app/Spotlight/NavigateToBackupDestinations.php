<?php

declare(strict_types=1);

namespace App\Spotlight;

use LivewireUI\Spotlight\Spotlight;
use LivewireUI\Spotlight\SpotlightCommand;

class NavigateToBackupDestinations extends SpotlightCommand
{
    /**
     * The name of the command shown in the Spotlight component.
     */
    protected string $name = 'Go to Backup Destinations';

    /**
     * A brief description of the command's purpose.
     */
    protected string $description = 'Navigate to the backup destinations page.';

    /**
     * Additional search terms to help users find this command.
     *
     * @var array<int, string>
     */
    protected array $synonyms = [
        'backups',
        'destinations',
        'backup destinations',
        'folders',
        'save',
    ];

    /**
     * Execute the command, redirecting the user to the backup destinations page.
     */
    public function execute(Spotlight $spotlight): void
    {
        $spotlight->redirect(route('backup-destinations.index'));
    }

    /**
     * Determine if this command should be displayed in the Spotlight component.
     */
    public function shouldBeShown(): bool
    {
        return true;
    }
}
