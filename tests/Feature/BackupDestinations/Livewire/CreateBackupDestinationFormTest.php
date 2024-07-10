<?php

declare(strict_types=1);

use App\Livewire\BackupDestinations\CreateBackupDestinationForm;
use App\Models\BackupDestination;
use App\Models\User;

it('renders successfully', function (): void {

    Livewire::test(CreateBackupDestinationForm::class)
        ->assertStatus(200);
});

it('submits s3 successfully', function (): void {
    $user = User::factory()->create();

    $testData = [
        'label' => 'Test Backup Destination',
        'type' => 'custom_s3',
        's3AccessKey' => 'test-access-key',
        's3SecretKey' => 'test-secret-key',
        's3BucketName' => 'test-bucket-name',
        'customS3Region' => 'test-region',
        'customS3Endpoint' => 'test-endpoint',
    ];

    $component = Livewire::actingAs($user)
        ->test(CreateBackupDestinationForm::class)
        ->set('label', $testData['label'])
        ->set('type', $testData['type'])
        ->set('s3AccessKey', $testData['s3AccessKey'])
        ->set('s3SecretKey', $testData['s3SecretKey'])
        ->set('s3BucketName', $testData['s3BucketName'])
        ->set('customS3Region', $testData['customS3Region'])
        ->set('customS3Endpoint', $testData['customS3Endpoint'])
        ->call('submit');

    $this->assertDatabaseHas('backup_destinations', [
        'user_id' => $user->id,
        'label' => $testData['label'],
        'type' => $testData['type'],
        'custom_s3_region' => $testData['customS3Region'],
    ]);

    $backupDestination = BackupDestination::where('user_id', $user->id)
        ->where('label', $testData['label'])
        ->first();

    $this->assertNotNull($backupDestination);

    $this->assertNotNull($backupDestination->s3_access_key);
    $this->assertEquals($testData['s3AccessKey'], $backupDestination->s3_access_key);
    $this->assertNotNull($backupDestination->s3_secret_key);
    $this->assertEquals($testData['s3SecretKey'], $backupDestination->s3_secret_key);
    $this->assertNotNull($backupDestination->s3_bucket_name);
    $this->assertEquals($testData['s3BucketName'], $backupDestination->s3_bucket_name);
    $this->assertNotNull($backupDestination->custom_s3_endpoint);
    $this->assertEquals($testData['customS3Endpoint'], $backupDestination->custom_s3_endpoint);

    $this->assertNotEquals($testData['s3AccessKey'], $backupDestination->getAttributes()['s3_access_key']);
    $this->assertNotEquals($testData['s3SecretKey'], $backupDestination->getAttributes()['s3_secret_key']);
    $this->assertNotEquals($testData['s3BucketName'], $backupDestination->getAttributes()['s3_bucket_name']);
    $this->assertNotEquals($testData['customS3Endpoint'], $backupDestination->getAttributes()['custom_s3_endpoint']);

    $component->assertRedirect(route('backup-destinations.index'));
    $this->assertAuthenticated();
});

it('validates required fields', function (): void {

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

it('validates custom S3 fields', function (): void {

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
