<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Modals;

use App\Models\BackupTask;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Toaster;

/**
 * Manages the display of a backup task's webhook URL in a modal.
 */
class WebhookModal extends Component
{
    /** @var int The ID of the backup task */
    public int $backupTaskId;

    /** @var string|null The webhook URL */
    public ?string $webhookUrl = null;

    /**
     * Initialize the component with a backup task.
     */
    public function mount(BackupTask|int $backupTask): void
    {
        $this->backupTaskId = $backupTask instanceof BackupTask
            ? $backupTask->getAttribute('id')
            : $backupTask;

        $this->loadWebhookUrl();
    }

    /**
     * Load the current webhook URL.
     */
    public function loadWebhookUrl(): void
    {
        $backupTask = BackupTask::findOrFail($this->backupTaskId);
        $this->webhookUrl = $backupTask->getAttribute('webhook_url');
    }

    /**
     * Refresh the webhook token with a new one.
     */
    public function refreshToken(): void
    {
        $backupTask = BackupTask::findOrFail($this->backupTaskId);
        $backupTask->refreshWebhookToken();
        Toaster::success('Webhook token regenerated.');
        $this->loadWebhookUrl();
    }

    /**
     * Render the webhook modal component.
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.modals.webhook-modal', [
            'backupTask' => BackupTask::findOrFail($this->backupTaskId),
        ]);
    }
}
