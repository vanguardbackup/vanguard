<?php

declare(strict_types=1);

use App\Livewire\BackupDestinations\CheckConnectionButton;
use App\Models\BackupDestination;

it('can be rendered', function () {

    $component = Livewire::test(CheckConnectionButton::class, ['backupDestination' => BackupDestination::factory()->create()]);

    $component->assertOk();
});

it('can check s3 connection', function () {
    Queue::fake();
    Event::fake();
    Toaster::fake();

    $backupDestination = BackupDestination::factory()->create(['type' => 's3']);

    $component = Livewire::test(CheckConnectionButton::class, ['backupDestination' => $backupDestination]);

    $component->call('checkConnection');

    $component->assertDispatched('backup-destination-connection-check-initiated-' . $backupDestination->id);

    Toaster::assertDispatched(__('Performing a connectivity check.'));
});

it('can refresh self', function () {
    $component = Livewire::test(CheckConnectionButton::class, ['backupDestination' => BackupDestination::factory()->create()]);

    $component->call('refreshSelf');

    $component->assertSet('backupDestination', $component->get('backupDestination'));
});
