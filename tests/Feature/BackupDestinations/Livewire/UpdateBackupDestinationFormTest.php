<?php

declare(strict_types=1);

use App\Livewire\BackupDestinations\UpdateBackupDestinationForm;
use App\Models\BackupDestination;
use App\Models\User;

test('it renders the component successfully', function () {

    Livewire::test(UpdateBackupDestinationForm::class, ['backupDestination' => BackupDestination::factory()->create()])
        ->assertStatus(200);
});

test('the creator of the backup destination can update the backup destination', function () {

    $user = User::factory()->create();
    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $user->id,
        'path_style_endpoint' => false,
    ]);

    $component = Livewire::actingAs($user)
        ->test(UpdateBackupDestinationForm::class, ['backupDestination' => $backupDestination])
        ->set('label', 'New Name')
        ->set('type', 'custom_s3')
        ->set('s3AccessKey', 'access-key')
        ->set('s3SecretKey', 'secret-key')
        ->set('s3BucketName', 'bucket-name')
        ->set('customS3Region', 'region')
        ->set('customS3Endpoint', 'endpoint')
        ->set('usePathStyleEndpoint', true)
        ->call('submit');

    $component->assertRedirect(route('backup-destinations.index'));

    $this->assertDatabaseHas('backup_destinations', [
        'label' => 'New Name',
        'type' => 'custom_s3',
        's3_access_key' => 'access-key',
        's3_secret_key' => 'secret-key',
        's3_bucket_name' => 'bucket-name',
        'custom_s3_region' => 'region',
        'custom_s3_endpoint' => 'endpoint',
        'path_style_endpoint' => true,
    ]);

    $this->assertEquals($user->id, $backupDestination->fresh()->user_id);

    $this->assertAuthenticatedAs($user);
});

test('another user cannot update the backup destination', function () {

    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $user->id,
        'path_style_endpoint' => false,
    ]);

    Livewire::actingAs($anotherUser)
        ->test(UpdateBackupDestinationForm::class, ['backupDestination' => $backupDestination])
        ->set('label', 'New Name')
        ->set('type', 'custom_s3')
        ->set('s3AccessKey', 'access-key')
        ->set('s3SecretKey', 'secret-key')
        ->set('s3BucketName', 'bucket-name')
        ->set('customS3Region', 'region')
        ->set('customS3Endpoint', 'endpoint')
        ->set('usePathStyleEndpoint', true)
        ->call('submit')
        ->assertForbidden();

    $this->assertDatabaseHas('backup_destinations', [
        'label' => $backupDestination->label,
        'type' => $backupDestination->type,
        's3_access_key' => $backupDestination->s3_access_key,
        's3_secret_key' => $backupDestination->s3_secret_key,
        's3_bucket_name' => $backupDestination->s3_bucket_name,
        'custom_s3_region' => $backupDestination->custom_s3_region,
        'custom_s3_endpoint' => $backupDestination->custom_s3_endpoint,
        'path_style_endpoint' => $backupDestination->path_style_endpoint,
    ]);

    $this->assertAuthenticatedAs($anotherUser);
});

test('the type must be valid', function () {

    $user = User::factory()->create();
    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $user->id,
        'path_style_endpoint' => false,
    ]);

    Livewire::actingAs($user)
        ->test(UpdateBackupDestinationForm::class, ['backupDestination' => $backupDestination])
        ->set('type', 'invalid-type')
        ->call('submit')
        ->assertHasErrors(['type' => 'in']);
});
