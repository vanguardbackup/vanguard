<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Buttons;

use App\Models\BackupTask;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * Manages the button for toggling the favourite state of a backup task.
 *
 * This component handles the UI and logic for favouring and unfavouring a backup task,
 * including rate limiting to prevent abuse.
 */
class ToggleFavouriteButton extends Component
{
    use WithRateLimiting;

    /** @var BackupTask The backup task to be toggled */
    public BackupTask $backupTask;

    /**
     * Refresh the component.
     *
     * Dispatches a refresh event to update the component's state.
     */
    public function refreshSelf(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * Toggle the favourite state of the backup task.
     *
     * Attempts to toggle the task's state while respecting rate limits.
     * Notifies the user of the action's result.
     */
    public function toggleFavouriteState(): void
    {
        try {
            $this->ensureNotRateLimited();
            $this->executeStateToggle();
        } catch (TooManyRequestsException) {
            $this->notifyRateLimitReached();
        }
    }

    /**
     * Render the toggle favourite button component.
     *
     * @return View The component's view
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.buttons.toggle-favourite-button');
    }

    /**
     * Get the event listeners for the component.
     *
     * @return array<string, string> The event listeners
     */
    protected function getListeners(): array
    {
        $taskId = $this->backupTask->getAttribute('id');

        return [
            "task-button-clicked-{$taskId}" => 'refreshSelf',
            "echo-private:backup-tasks.{$taskId},BackupTaskStatusChanged" => 'refreshSelf',
            'backup-task-status-changed' => 'refreshSelf',
            'refresh-backup-tasks-table' => 'refreshSelf',
        ];
    }

    /**
     * Ensure the action is not rate limited.
     *
     * @throws TooManyRequestsException When rate limit is exceeded
     */
    private function ensureNotRateLimited(): void
    {
        $this->rateLimit(10);
    }

    /**
     * Execute the state toggle action.
     *
     * Determines the current state and calls the appropriate method to change it.
     * Dispatches events after the state change.
     */
    private function executeStateToggle(): void
    {
        $toggleAction = $this->backupTask->isFavourited()
            ? [$this, 'unfavouriteBackupTask']
            : [$this, 'favouriteBackupTask'];

        $toggleAction();

        $this->dispatch('toggle-favourite-button-clicked-' . $this->backupTask->getAttribute('id'));
        $this->dispatch('refresh-backup-tasks-table');
        $this->dispatch('refreshBackupTasksTable');
    }

    /**
     * Favourite the backup task.
     *
     * Changes the task's state to resumed and notifies the user.
     */
    private function favouriteBackupTask(): void
    {
        $this->backupTask->favourite();
        Toaster::success('Backup task has been pinned.');
    }

    /**
     * Unfavourite the backup task.
     *
     * Changes the task's state to paused and notifies the user.
     */
    private function unfavouriteBackupTask(): void
    {
        $this->backupTask->unfavourite();
        Toaster::success('Backup task has been unpinned.');
    }

    /**
     * Notify the user that the rate limit has been reached.
     *
     * Displays an error message to the user.
     */
    private function notifyRateLimitReached(): void
    {
        Toaster::error('You are doing this too often.');
    }
}
