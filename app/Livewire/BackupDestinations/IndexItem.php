<?php

namespace App\Livewire\BackupDestinations;

use App\Models\BackupDestination;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class IndexItem extends Component
{
    public BackupDestination $backupDestination;

    public function getListeners(): array
    {
        return [
            "echo:backup-destinations.{$this->backupDestination->id},BackupDestinationConnectionCheck" => 'echoReceivedEvent',
            "backup-destination-connection-check-initiated-{$this->backupDestination->id}" => 'updateLivewireComponents',
        ];
    }

    public function echoReceivedEvent(): void
    {
        if ($this->backupDestination->isReachable()) {
            Toaster::success(__('The connection to the backup destination has been established.'));
        } else {
            Toaster::error(__('The connection to the backup destination could not be established. Please check the credentials.'));
        }

        Log::debug('Received the BackupDestinationConnectionCheck event. Refreshing the component.');
        $this->dispatch('$refresh');
    }

    public function updateLivewireComponents(): void
    {
        $this->dispatch('$refresh');

        // This will refresh the "CheckConnectionButton" component via its listener.
        $this->dispatch('update-backup-destination-check-button-'.$this->backupDestination->id);
    }

    public function mount(BackupDestination $backupDestination): void
    {
        $this->backupDestination = $backupDestination;
    }

    public function render(): View
    {
        return view('livewire.backup-destinations.index-item');
    }
}
