<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\CheckRemoteServerConnectionJob;
use App\Jobs\RemoteServers\RemoveServerJob;
use App\Jobs\RemoteServers\RemoveSSHKeyJob;
use Database\Factories\RemoteServerFactory;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Represents a remote server in the system.
 *
 * This model handles remote server connections, status management,
 * and associated backup tasks.
 */
class RemoteServer extends Model
{
    /** @use HasFactory<RemoteServerFactory> */
    use HasFactory;

    public const string STATUS_ONLINE = 'online';
    public const string STATUS_OFFLINE = 'offline';
    public const string STATUS_UNKNOWN = 'unknown';
    public const string STATUS_CHECKING = 'checking';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    public $guarded = [];

    /**
     * Get the user that owns the remote server.
     *
     * @return BelongsTo<User, RemoteServer>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the backup tasks for the remote server.
     *
     * @return HasMany<BackupTask>
     */
    public function backupTasks(): HasMany
    {
        return $this->hasMany(BackupTask::class);
    }

    /**
     * Update the last connected timestamp for the server.
     */
    public function updateLastConnectedAt(): void
    {
        $this->update(['last_connected_at' => now()]);
        $this->save();
    }

    /**
     * Check if the server has a database password.
     */
    public function hasDatabasePassword(): bool
    {
        return ! empty($this->database_password);
    }

    /**
     * Get the decrypted database password for the server.
     */
    public function getDecryptedDatabasePassword(): ?string
    {
        if (! $this->hasDatabasePassword()) {
            Log::error('[DATABASE PASSWORD] Tried to get decrypted database password for remote server without a password.');

            return null;
        }

        /** @var string $databasePassword */
        $databasePassword = $this->database_password;

        return Crypt::decryptString($databasePassword);
    }

    /**
     * Check if the database password is encrypted.
     */
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

    /**
     * Mark the server status as checking.
     */
    public function markAsChecking(): void
    {
        $this->update(['connectivity_status' => self::STATUS_CHECKING]);
        $this->save();
        $this->refresh();
    }

    /**
     * Mark the server status as online.
     */
    public function markAsOnline(): void
    {
        $this->update(['connectivity_status' => self::STATUS_ONLINE]);
        $this->save();
        $this->refresh();
    }

    /**
     * Mark the server as online if its status is not already online.
     */
    public function markAsOnlineIfStatusIsNotOnline(): void
    {
        if ($this->isOnline()) {
            return;
        }

        if ($this->connectivity_status !== self::STATUS_ONLINE) {
            $this->markAsOnline();
        }
    }

    /**
     * Run a server connection check.
     */
    public function runServerConnectionCheck(): void
    {
        $this->markAsChecking();

        CheckRemoteServerConnectionJob::dispatch($this->id)
            ->onQueue('connectivity-checks');
    }

    /**
     * Check if the server status is online.
     */
    public function isOnline(): bool
    {
        return $this->connectivity_status === self::STATUS_ONLINE;
    }

    /**
     * Check if the server status is offline.
     */
    public function isOffline(): bool
    {
        return $this->connectivity_status === self::STATUS_OFFLINE;
    }

    /**
     * Check if the server status is checking.
     */
    public function isChecking(): bool
    {
        return $this->connectivity_status === self::STATUS_CHECKING;
    }

    /**
     * Check if the server status is unknown.
     */
    public function isUnknown(): bool
    {
        return $this->connectivity_status === self::STATUS_UNKNOWN;
    }

    /**
     * Check if the server is marked for deletion.
     */
    public function isMarkedForDeletion(): bool
    {
        return ! empty($this->marked_for_deletion_at);
    }

    /**
     * Mark the server for deletion.
     */
    public function setMarkedForDeletion(): void
    {
        $this->update(['marked_for_deletion_at' => now()]);
        $this->save();
        $this->refresh();
    }

    /**
     * Remove the server.
     */
    public function removeServer(): void
    {
        $this->setMarkedForDeletion();
        $this->removeSSHKey();

        // We delay so the key has time to be removed before the server is removed!
        RemoveServerJob::dispatch($this)
            ->delay(now()->addMinutes(2));
    }

    /**
     * Remove the SSH key for the server.
     */
    public function removeSSHKey(): void
    {
        RemoveSSHKeyJob::dispatch($this);
    }
}
