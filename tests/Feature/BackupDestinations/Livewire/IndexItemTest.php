<?php

use App\Livewire\BackupDestinations\IndexItem;
use App\Models\BackupDestination;

it('can be rendered', function () {

    $backupDestination = BackupDestination::factory()->create();

    $component = Livewire::test(IndexItem::class, ['backupDestination' => $backupDestination]);

    $component->assertOk();
});

it('can receive the BackupDestinationConnectionCheck event', function () {
    Toaster::fake();

    $backupDestination = BackupDestination::factory()->reachable()->create();

    $component = Livewire::test(IndexItem::class, ['backupDestination' => $backupDestination]);

    $component->call('echoReceivedEvent');

    Toaster::assertDispatched(__('The connection to the backup destination has been established.'));
    $component->assertDispatched('$refresh');
});

it('can receive the BackupDestinationConnectionCheck event when the connection is not reachable', function () {
    Toaster::fake();

    $backupDestination = BackupDestination::factory()->unreachable()->create();

    $component = Livewire::test(IndexItem::class, ['backupDestination' => $backupDestination]);

    $component->call('echoReceivedEvent');

    Toaster::assertDispatched(__('The connection to the backup destination could not be established. Please check the credentials.'));
    $component->assertDispatched('$refresh');
});

it('can update the Livewire components', function () {
    $backupDestination = BackupDestination::factory()->create();

    $component = Livewire::test(IndexItem::class, ['backupDestination' => $backupDestination]);

    $component->call('updateLivewireComponents');

    $component->assertDispatched('$refresh');
    $component->assertDispatched('update-backup-destination-check-button-' . $backupDestination->id);
});
