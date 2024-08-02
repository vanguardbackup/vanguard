<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Manages the index view for backup tasks.
 *
 * This Livewire component handles the display of backup tasks in the index view.
 */
class Index extends Component
{
    /**
     * Render the backup tasks index view.
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.index');
    }
}
