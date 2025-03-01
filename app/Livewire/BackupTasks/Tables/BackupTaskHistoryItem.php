<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Tables;

use App\Models\BackupTaskLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Livewire\Component;

class BackupTaskHistoryItem extends Component
{
    /**
     * The backup task log.
     */
    public BackupTaskLog $backupTaskLog;

    /**
     * Mount the component and initialize properties.
     */
    public function mount(): void
    {
        // No initialization needed as we're using methods instead of properties
    }

    /**
     * Render the backup task history item.
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.tables.backup-task-history-item');
    }

    /**
     * Get the formatted created date.
     */
    public function formattedDate(): string
    {
        if (! $this->backupTaskLog->getAttribute('created_at')) {
            return __('Never');
        }

        $timezone = Auth::user()->timezone ?? Config::get('app.timezone');

        return $this->backupTaskLog->getAttribute('created_at')
            ->timezone($timezone)
            ->format('d M Y H:i');
    }

    /**
     * Get the formatted detailed date for the modal.
     */
    public function detailedDate(): string
    {
        if (! $this->backupTaskLog->getAttribute('created_at')) {
            return __('Never');
        }

        $timezone = Auth::user()->timezone ?? Config::get('app.timezone');

        return $this->backupTaskLog->getAttribute('created_at')
            ->timezone($timezone)
            ->format('l, d F Y H:i');
    }
}
