<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'github_id',
        'preferred_backup_destination_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function gravatar(): string
    {
        $hash = md5(strtolower(trim($this->email)));

        return "https://www.gravatar.com/avatar/{$hash}";
    }

    public function getFirstName(): string
    {
        return explode(' ', $this->name)[0];
    }

    public function getLastName(): string
    {
        $nameParts = explode(' ', $this->name);
        if (count($nameParts) > 1) {
            return end($nameParts);
        }

        return '';
    }

    /**
     * @return HasMany<RemoteServer>
     */
    public function remoteServers(): HasMany
    {
        return $this->hasMany(RemoteServer::class);
    }

    /**
     * @return HasMany<BackupDestination>
     */
    public function backupDestinations(): HasMany
    {
        return $this->hasMany(BackupDestination::class);
    }

    /**
     * @return HasMany<BackupTask>
     */
    public function backupTasks(): HasMany
    {
        return $this->hasMany(BackupTask::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->email, config('auth.admin_email_addresses'), true);
    }

    public function backupTaskLogCount(): int
    {
        return BackupTaskLog::whereHas('backupTask', function ($query) {
            $query->where('user_id', $this->id);
            $query->whereNotNull('finished_at');
        })->count();
    }

    public function backupTasklogCountToday(): int
    {
        return BackupTaskLog::whereHas('backupTask', function ($query) {
            $query->where('user_id', $this->id);
        })->whereDate('created_at', today()->timezone($this->timezone ?? 'UTC'))->count();
    }

    public function canLoginWithGithub(): bool
    {
        return $this->github_id !== null;
    }

    /**
     * @return HasMany<Tag>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function firstName(): Attribute
    {
        return new Attribute(
            get: fn () => $this->getFirstName(),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function lastName(): Attribute
    {
        return new Attribute(
            get: fn () => $this->getLastName(),
        );
    }

    /**
     * Get the casts array.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
