<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'github_id',
        'preferred_backup_destination_id',
        'language',
        'gravatar_email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the Gravatar URL for the user.
     */
    public function gravatar(int|float|null $size = 80): string
    {
        $email = $this->gravatar_email ?? $this->email;

        $size = $size > 0 ? (int) $size : 80;
        $sizeQuery = '?s=' . $size;

        return sprintf(
            'https://www.gravatar.com/avatar/%s%s',
            md5(strtolower(trim($email))),
            $sizeQuery
        );
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
        return BackupTaskLog::whereHas('backupTask', function ($query): void {
            $query->where('user_id', $this->id);
            $query->whereNotNull('finished_at');
        })->count();
    }

    public function backupTasklogCountToday(): int
    {
        return BackupTaskLog::whereHas('backupTask', function ($query): void {
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
            get: fn (): string => $this->getFirstName(),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function lastName(): Attribute
    {
        return new Attribute(
            get: fn (): string => $this->getLastName(),
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
