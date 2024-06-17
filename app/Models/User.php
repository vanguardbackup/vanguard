<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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

    public function remoteServers(): HasMany
    {
        return $this->hasMany(RemoteServer::class);
    }

    public function backupDestinations(): HasMany
    {
        return $this->hasMany(BackupDestination::class);
    }

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

    protected function firstName(): Attribute
    {
        return new Attribute(function ($value) {
            return $this->getFirstName();
        });
    }

    protected function lastName(): Attribute
    {
        return new Attribute(function ($value) {
            return $this->getLastName();
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
