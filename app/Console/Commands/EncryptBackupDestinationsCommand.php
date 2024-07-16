<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BackupDestination;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

class EncryptBackupDestinationsCommand extends Command
{
    protected $signature = 'vanguard:encrypt-backup-destinations';

    protected $description = 'Encrypts any unencrypted sensitive fields in backup destinations stored in the database.';

    /**
     * @var array|string[]
     */
    protected array $fieldsToEncrypt = [
        's3_access_key',
        's3_secret_key',
        's3_bucket_name',
        'custom_s3_endpoint',
    ];

    public function handle(): void
    {
        $backupDestinations = BackupDestination::all();

        if ($backupDestinations->isEmpty()) {
            $this->components->error('No backup destinations found.');

            return;
        }

        $newlyEncryptedCount = $this->encryptBackupDestinations($backupDestinations);

        $this->components->info($newlyEncryptedCount . ' backup destinations have been updated with encrypted fields.');
    }

    /**
     * @param  Collection<int, BackupDestination>  $backupDestinations
     */
    protected function encryptBackupDestinations(Collection $backupDestinations): int
    {
        $newlyEncryptedCount = 0;

        foreach ($backupDestinations as $backupDestination) {
            if ($this->encryptBackupDestination($backupDestination)) {
                $newlyEncryptedCount++;
            }
        }

        return $newlyEncryptedCount;
    }

    protected function encryptBackupDestination(BackupDestination $backupDestination): bool
    {
        $updated = false;

        foreach ($this->fieldsToEncrypt as $fieldToEncrypt) {
            if ($this->encryptField($backupDestination, $fieldToEncrypt)) {
                $updated = true;
            }
        }

        if ($updated) {
            $backupDestination->save();
        }

        return $updated;
    }

    protected function encryptField(BackupDestination $backupDestination, string $field): bool
    {
        $value = $backupDestination->getAttribute($field);

        if (empty($value)) {
            $this->components->warn(sprintf("Field '%s' for backup destination %s is empty. Skipping encryption.", $field, $backupDestination->getAttribute('label')));

            return false;
        }

        if ($this->isEncrypted($value)) {
            return false;
        }

        $backupDestination->setAttribute($field, Crypt::encryptString($value));

        return true;
    }

    protected function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
