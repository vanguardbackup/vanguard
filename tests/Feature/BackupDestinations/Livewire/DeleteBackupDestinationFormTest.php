<?php

declare(strict_types=1);

use App\Livewire\BackupDestinations\DeleteBackupDestinationForm;
use App\Models\BackupDestination;
use App\Models\User;

test('the component can be rendered', function () {

    Livewire::test(DeleteBackupDestinationForm::class, ['backupDestination' => BackupDestination::factory()->create()])
        ->assertStatus(200);
});

test('a backup destination can be deleted by its creator', function () {

    $user = User::factory()->create();
    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(DeleteBackupDestinationForm::class, ['backupDestination' => $backupDestination])
        ->call('delete');

    $this->assertDatabaseMissing('backup_destinations', ['id' => $backupDestination->id]);
    $this->assertAuthenticatedAs($user);
});

test('a backup destination cannot be deleted by another user', function () {

    $user = User::factory()->create();
    $backupDestination = BackupDestination::factory()->create();

    $this->actingAs($user);

    Livewire::test(DeleteBackupDestinationForm::class, ['backupDestination' => BackupDestination::factory()->create()])
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('backup_destinations', ['id' => $backupDestination->id]);
    $this->assertAuthenticatedAs($user);
});
