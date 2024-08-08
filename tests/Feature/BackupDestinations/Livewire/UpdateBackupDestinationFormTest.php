<?php

declare(strict_types=1);

use App\Livewire\BackupDestinations\UpdateBackupDestinationForm;
use App\Models\BackupDestination;
use App\Models\User;

test('it renders the component successfully', function (): void {

    Livewire::test(UpdateBackupDestinationForm::class, ['backupDestination' => BackupDestination::factory()->create()])
        ->assertStatus(200);
});

test('the creator of the backup destination can update the backup destination', function (): void {
    $this->withoutExceptionHandling();
    $user = User::factory()->create();

    $originalData = [
        'label' => 'Original Label',
        'type' => 'custom_s3',
        's3_access_key' => 'original-access-key',
        's3_secret_key' => 'original-secret-key',
        's3_bucket_name' => 'original-bucket-name',
        'custom_s3_region' => 'original-region',
        'custom_s3_endpoint' => 'original-endpoint',
        'path_style_endpoint' => false,
    ];

    $backupDestination = BackupDestination::factory()->create(array_merge(
        $originalData,
        ['user_id' => $user->id]
    ));

    $newData = [
        'label' => 'New Name',
        'type' => 'custom_s3',
        's3AccessKey' => 'new-access-key',
        's3SecretKey' => 'new-secret-key',
        's3BucketName' => 'new-bucket-name',
        'customS3Region' => 'new-region',
        'customS3Endpoint' => 'new-endpoint',
        'usePathStyleEndpoint' => true,
    ];

    $component = Livewire::actingAs($user)
        ->test(UpdateBackupDestinationForm::class, ['backupDestination' => $backupDestination])
        ->set('label', $newData['label'])
        ->set('type', $newData['type'])
        ->set('s3AccessKey', $newData['s3AccessKey'])
        ->set('s3SecretKey', $newData['s3SecretKey'])
        ->set('s3BucketName', $newData['s3BucketName'])
        ->set('customS3Region', $newData['customS3Region'])
        ->set('customS3Endpoint', $newData['customS3Endpoint'])
        ->set('usePathStyleEndpoint', $newData['usePathStyleEndpoint'])
        ->call('submit');

    $component->assertRedirect(route('backup-destinations.index'));

    $updatedBackupDestination = $backupDestination->fresh();

    $this->assertEquals($newData['label'], $updatedBackupDestination->label);
    $this->assertEquals($newData['type'], $updatedBackupDestination->type);
    $this->assertEquals($newData['s3AccessKey'], $updatedBackupDestination->s3_access_key);
    $this->assertEquals($newData['s3SecretKey'], $updatedBackupDestination->s3_secret_key);
    $this->assertEquals($newData['s3BucketName'], $updatedBackupDestination->s3_bucket_name);
    $this->assertEquals($newData['customS3Region'], $updatedBackupDestination->custom_s3_region);
    $this->assertEquals($newData['customS3Endpoint'], $updatedBackupDestination->custom_s3_endpoint);
    $this->assertEquals($newData['usePathStyleEndpoint'], $updatedBackupDestination->path_style_endpoint);

    $this->assertNotEquals($newData['s3AccessKey'], $updatedBackupDestination->getAttributes()['s3_access_key']);
    $this->assertNotEquals($newData['s3SecretKey'], $updatedBackupDestination->getAttributes()['s3_secret_key']);
    $this->assertNotEquals($newData['s3BucketName'], $updatedBackupDestination->getAttributes()['s3_bucket_name']);
    $this->assertNotEquals($newData['customS3Endpoint'], $updatedBackupDestination->getAttributes()['custom_s3_endpoint']);

    $this->assertNotEquals($originalData['s3_access_key'], $updatedBackupDestination->getAttributes()['s3_access_key']);
    $this->assertNotEquals($originalData['s3_secret_key'], $updatedBackupDestination->getAttributes()['s3_secret_key']);
    $this->assertNotEquals($originalData['s3_bucket_name'], $updatedBackupDestination->getAttributes()['s3_bucket_name']);
    $this->assertNotEquals($originalData['custom_s3_endpoint'], $updatedBackupDestination->getAttributes()['custom_s3_endpoint']);

    $this->assertEquals($user->id, $updatedBackupDestination->user_id);

    $this->assertAuthenticatedAs($user);
});

test('another user cannot update the backup destination', function (): void {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();

    $originalData = [
        'label' => 'Original Label',
        'type' => 'custom_s3',
        's3_access_key' => 'original-access-key',
        's3_secret_key' => 'original-secret-key',
        's3_bucket_name' => 'original-bucket-name',
        'custom_s3_region' => 'original-region',
        'custom_s3_endpoint' => 'original-endpoint',
        'path_style_endpoint' => false,
    ];

    $backupDestination = BackupDestination::factory()->create(array_merge(
        $originalData,
        ['user_id' => $user->id]
    ));

    $newData = [
        'label' => 'New Name',
        'type' => 'custom_s3',
        's3AccessKey' => 'new-access-key',
        's3SecretKey' => 'new-secret-key',
        's3BucketName' => 'new-bucket-name',
        'customS3Region' => 'new-region',
        'customS3Endpoint' => 'new-endpoint',
        'usePathStyleEndpoint' => true,
    ];

    Livewire::actingAs($anotherUser)
        ->test(UpdateBackupDestinationForm::class, ['backupDestination' => $backupDestination])
        ->set('label', $newData['label'])
        ->set('type', $newData['type'])
        ->set('s3AccessKey', $newData['s3AccessKey'])
        ->set('s3SecretKey', $newData['s3SecretKey'])
        ->set('s3BucketName', $newData['s3BucketName'])
        ->set('customS3Region', $newData['customS3Region'])
        ->set('customS3Endpoint', $newData['customS3Endpoint'])
        ->set('usePathStyleEndpoint', $newData['usePathStyleEndpoint'])
        ->call('submit')
        ->assertForbidden();

    $backupDestination->refresh();

    $this->assertEquals($originalData['label'], $backupDestination->label);
    $this->assertEquals($originalData['type'], $backupDestination->type);
    $this->assertEquals($originalData['s3_access_key'], $backupDestination->s3_access_key);
    $this->assertEquals($originalData['s3_secret_key'], $backupDestination->s3_secret_key);
    $this->assertEquals($originalData['s3_bucket_name'], $backupDestination->s3_bucket_name);
    $this->assertEquals($originalData['custom_s3_region'], $backupDestination->custom_s3_region);
    $this->assertEquals($originalData['custom_s3_endpoint'], $backupDestination->custom_s3_endpoint);
    $this->assertEquals($originalData['path_style_endpoint'], $backupDestination->path_style_endpoint);

    $this->assertNotEquals($originalData['s3_access_key'], $backupDestination->getAttributes()['s3_access_key']);
    $this->assertNotEquals($originalData['s3_secret_key'], $backupDestination->getAttributes()['s3_secret_key']);
    $this->assertNotEquals($originalData['s3_bucket_name'], $backupDestination->getAttributes()['s3_bucket_name']);
    $this->assertNotEquals($originalData['custom_s3_endpoint'], $backupDestination->getAttributes()['custom_s3_endpoint']);

    $this->assertNotEquals($newData['s3AccessKey'], $backupDestination->getAttributes()['s3_access_key']);
    $this->assertNotEquals($newData['s3SecretKey'], $backupDestination->getAttributes()['s3_secret_key']);
    $this->assertNotEquals($newData['s3BucketName'], $backupDestination->getAttributes()['s3_bucket_name']);
    $this->assertNotEquals($newData['customS3Endpoint'], $backupDestination->getAttributes()['custom_s3_endpoint']);

    $this->assertAuthenticatedAs($anotherUser);
});

test('the type must be valid', function (): void {

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
