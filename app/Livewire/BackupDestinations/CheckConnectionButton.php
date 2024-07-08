<?php

declare(strict_types=1);

namespace App\Livewire\BackupDestinations;

use App\Models\BackupDestination;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class CheckConnectionButton extends Component
{
    public BackupDestination $backupDestination;

    /**
     * Get the listeners array.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            "echo-private:backup-destinations.{$this->backupDestination->getAttribute('id')},BackupDestinationConnectionCheck" => 'refreshSelf',
            "update-backup-destination-check-button-{$this->backupDestination->getAttribute('id')}" => '$refresh',
        ];
    }

    public function refreshSelf(): void
    {
        $this->dispatch('$refresh');
    }

    public function checkConnection(): void
    {
        $this->backupDestination->refresh();

        $this->backupDestination->markAsChecking();

        $this->backupDestination->run();

        Toaster::info(__('Performing a connectivity check.'));

        $this->dispatch("backup-destination-connection-check-initiated-{$this->backupDestination->getAttribute('id')}");
    }

    public function render(): View
    {
        return view('livewire.backup-destinations.check-connection-button');
    }
}
