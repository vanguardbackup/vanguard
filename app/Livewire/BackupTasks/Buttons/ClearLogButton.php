<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Buttons;

use Override;
use App\Models\BackupTaskLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * Manages the button for clearing backup task logs.
 *
 * This component handles the UI and logic for removing all backup task logs
 * associated with the authenticated user.
 */
class ClearLogButton extends Component
{
    /**
     * Clear all backup task logs for the authenticated user.
     *
     * Removes logs, displays a success message, and dispatches refresh events.
     */
    public function clearAllLogs(): void
    {
        BackupTaskLog::whereHas('backupTask', function ($query): void {
            $query->where('user_id', Auth::id());
        })->delete();

        Toaster::success('All your backup task logs have been removed.');

        $this->dispatch('refreshBackupTaskHistory');
        $this->dispatch('close-modal', 'clear-all-backup-task-logs');
    }

    /**
     * Render the clear log button component.
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.buttons.clear-log-button');
    }

    /**
     * Get the event listeners for the component.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function getListeners(): array
    {
        return [
            'refreshBackupTaskHistory' => '$refresh',
        ];
    }
}
