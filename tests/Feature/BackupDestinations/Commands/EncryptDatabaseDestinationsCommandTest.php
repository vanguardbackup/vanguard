<?php

declare(strict_types=1);

use App\Console\Commands\EncryptBackupDestinationsCommand;
use App\Models\BackupDestination;
use Illuminate\Support\Facades\Crypt;

it('exits if there are no backup destinations', function (): void {
    $this->artisan(EncryptBackupDestinationsCommand::class)
        ->expectsOutputToContain('No backup destinations found.')
        ->assertExitCode(0);
});

it('skips encryption if the field is empty', function (): void {
    $backupDestination = BackupDestination::factory()->create([
        'label' => 'Test Destination',
        's3_access_key' => '',
        's3_secret_key' => null,
        's3_bucket_name' => 'test-bucket',
        'custom_s3_endpoint' => '',
    ]);

    $this->artisan(EncryptBackupDestinationsCommand::class)
        ->expectsOutputToContain("Field 's3_access_key' for backup destination Test Destination is empty. Skipping encryption.")
        ->expectsOutputToContain("Field 's3_secret_key' for backup destination Test Destination is empty. Skipping encryption.")
        ->expectsOutputToContain("Field 'custom_s3_endpoint' for backup destination Test Destination is empty. Skipping encryption.")
        ->expectsOutputToContain('1 backup destinations have been updated with encrypted fields.')
        ->assertExitCode(0);

    $updatedDestination = $backupDestination->fresh();
    expect($updatedDestination->s3_access_key)->toBe('')
        ->and($updatedDestination->s3_secret_key)->toBeNull()
        ->and($updatedDestination->s3_bucket_name)->not->toBe('test-bucket')
        ->and(Crypt::decryptString($updatedDestination->s3_bucket_name))->toBe('test-bucket')
        ->and($updatedDestination->custom_s3_endpoint)->toBe('');
});

it('encrypts the fields', function (): void {
    $backupDestination = BackupDestination::factory()->create([
        's3_access_key' => 'test-access-key',
        's3_secret_key' => 'test-secret-key',
        's3_bucket_name' => 'test-bucket',
        'custom_s3_endpoint' => 'test-endpoint',
    ]);

    $this->artisan(EncryptBackupDestinationsCommand::class)
        ->expectsOutputToContain('1 backup destinations have been updated with encrypted fields.')
        ->assertExitCode(0);

    $updatedDestination = $backupDestination->fresh();

    expect($updatedDestination->s3_access_key)->not->toBe('test-access-key')
        ->and(Crypt::decryptString($updatedDestination->s3_access_key))->toBe('test-access-key')
        ->and($updatedDestination->s3_secret_key)->not->toBe('test-secret-key')
        ->and(Crypt::decryptString($updatedDestination->s3_secret_key))->toBe('test-secret-key')
        ->and($updatedDestination->s3_bucket_name)->not->toBe('test-bucket')
        ->and(Crypt::decryptString($updatedDestination->s3_bucket_name))->toBe('test-bucket')
        ->and($updatedDestination->custom_s3_endpoint)->not->toBe('test-endpoint')
        ->and(Crypt::decryptString($updatedDestination->custom_s3_endpoint))->toBe('test-endpoint');
});

it('does not encrypt the fields if they are already encrypted', function (): void {
    $backupDestination = BackupDestination::factory()->create([
        's3_access_key' => Crypt::encryptString('test-access-key'),
        's3_secret_key' => Crypt::encryptString('test-secret-key'),
        's3_bucket_name' => Crypt::encryptString('test-bucket'),
        'custom_s3_endpoint' => Crypt::encryptString('test-endpoint'),
    ]);

    $this->artisan(EncryptBackupDestinationsCommand::class)
        ->expectsOutputToContain('0 backup destinations have been updated with encrypted fields.')
        ->assertExitCode(0);

    $updatedDestination = $backupDestination->fresh();

    expect(Crypt::decryptString($updatedDestination->s3_access_key))->toBe('test-access-key')
        ->and(Crypt::decryptString($updatedDestination->s3_secret_key))->toBe('test-secret-key')
        ->and(Crypt::decryptString($updatedDestination->s3_bucket_name))->toBe('test-bucket')
        ->and(Crypt::decryptString($updatedDestination->custom_s3_endpoint))->toBe('test-endpoint');
});
