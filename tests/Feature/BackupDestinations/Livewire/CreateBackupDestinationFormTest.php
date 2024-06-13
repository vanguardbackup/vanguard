<?php

use App\Livewire\BackupDestinations\CreateBackupDestinationForm;
use App\Models\User;

it('renders successfully', function () {

    Livewire::test(CreateBackupDestinationForm::class)
        ->assertStatus(200);
});

it('submits s3 successfully', function () {

    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(CreateBackupDestinationForm::class)
        ->set('label', 'Test Backup Destination')
        ->set('type', 'custom_s3')
        ->set('s3AccessKey', 'test-access-key')
        ->set('s3SecretKey', 'test-secret-key')
        ->set('s3BucketName', 'test-bucket-name')
        ->set('customS3Region', 'test-region')
        ->set('customS3Endpoint', 'test-endpoint')
        ->call('submit');

    $this->assertDatabaseHas('backup_destinations', [
        'user_id' => $user->id,
        'label' => 'Test Backup Destination',
        'type' => 'custom_s3',
        's3_access_key' => 'test-access-key',
        's3_secret_key' => 'test-secret-key',
        's3_bucket_name' => 'test-bucket-name',
        'custom_s3_region' => 'test-region',
        'custom_s3_endpoint' => 'test-endpoint',
    ]);

    $component->assertRedirect(route('backup-destinations.index'));
    $this->assertAuthenticated();
});

it('validates required fields', function () {

    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(CreateBackupDestinationForm::class)
        ->set('type', '')
        ->call('submit');

    $component->assertHasErrors([
        'label' => ['required'],
        'type' => ['required'],
    ]);
});

it('validates custom S3 fields', function () {

    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(CreateBackupDestinationForm::class)
        ->set('type', 'custom_s3')
        ->call('submit');

    $component->assertHasErrors([
        's3AccessKey' => ['required_if'],
        's3SecretKey' => ['required_if'],
        's3BucketName' => ['required_if'],
        'customS3Endpoint' => ['required_if'],
    ]);
});
