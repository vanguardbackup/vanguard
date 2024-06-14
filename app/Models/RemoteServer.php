<?php

namespace App\Models;

use App\Jobs\CheckRemoteServerConnectionJob;
use App\Jobs\RemoteServers\RemoveServerJob;
use App\Jobs\RemoteServers\RemoveSSHKeyJob;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class RemoteServer extends Model
{
    use HasFactory;

    public const string STATUS_ONLINE = 'online';

    public const string STATUS_OFFLINE = 'offline';

    public const string STATUS_UNKNOWN = 'unknown';

    public const string STATUS_CHECKING = 'checking';

    public $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function backupTasks(): HasMany
    {
        return $this->hasMany(BackupTask::class);
    }

    public function updateLastConnectedAt(): void
    {
        $this->update(['last_connected_at' => now()]);
        $this->save();
    }

    public function hasDatabasePassword(): bool
    {
        return ! empty($this->database_password);
    }

    public function getDecryptedDatabasePassword(): ?string
    {
        if (! $this->hasDatabasePassword()) {
            Log::error('[DATABASE PASSWORD] Tried to get decrypted database password for remote server without a password.');

            return null;
        }

        return Crypt::decryptString($this->database_password);
    }

    public function isDatabasePasswordEncrypted(): bool
    {
        if (empty($this->database_password)) {
            return false;
        }

        try {
            Crypt::decryptString($this->database_password);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function markAsChecking(): void
    {
        $this->update(['connectivity_status' => self::STATUS_CHECKING]);
        $this->save();
        $this->refresh();
    }

    public function markAsOnline(): void
    {
        $this->update(['connectivity_status' => self::STATUS_ONLINE]);
        $this->save();
        $this->refresh();
    }

    public function markAsOnlineIfStatusIsNotOnline(): void
    {
        if ($this->isOnline()) {
            return;
        }

        if ($this->connectivity_status !== self::STATUS_ONLINE) {
            $this->markAsOnline();
        }
    }

    public function runServerConnectionCheck(): void
    {
        $this->markAsChecking();

        CheckRemoteServerConnectionJob::dispatch($this->id)
            ->onQueue('connectivity-checks');
    }

    public function isOnline(): bool
    {
        return $this->connectivity_status === self::STATUS_ONLINE;
    }

    public function isOffline(): bool
    {
        return $this->connectivity_status === self::STATUS_OFFLINE;
    }

    public function isChecking(): bool
    {
        return $this->connectivity_status === self::STATUS_CHECKING;
    }

    public function isUnknown(): bool
    {
        return $this->connectivity_status === self::STATUS_UNKNOWN;
    }

    public function isMarkedForDeletion(): bool
    {
        return ! empty($this->marked_for_deletion_at);
    }

    public function setMarkedForDeletion(): void
    {
        $this->update(['marked_for_deletion_at' => now()]);
        $this->save();
        $this->refresh();
    }

    public function removeServer(): void
    {
        $this->setMarkedForDeletion();
        $this->removeSSHKey();

        // We delay so the key has time to be removed before the server is removed!
        RemoveServerJob::dispatch($this)
            ->delay(now()->addMinutes(2));
    }

    public function removeSSHKey(): void
    {
        RemoveSSHKeyJob::dispatch($this);
    }
}
