<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\CheckBackupDestinationsS3ConnectionJob;
use Aws\S3\S3Client;
use Database\Factories\BackupDestinationFactory;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Motomedialab\SimpleLaravelAudit\Traits\AuditableModel;
use RuntimeException;

/**
 * Represents a backup destination in the system.
 *
 * This model handles various types of backup destinations including S3, custom S3, and local storage.
 * It provides methods for managing the connection status and interacting with S3 services.
 */
class BackupDestination extends Model
{
    use AuditableModel;
    /** @use HasFactory<BackupDestinationFactory> */
    use HasFactory;

    public const string TYPE_CUSTOM_S3 = 'custom_s3';
    public const string TYPE_S3 = 's3';
    public const string TYPE_LOCAL = 'local';

    public const string TYPE_DO_SPACES = 'digitalocean_spaces';

    public const string STATUS_REACHABLE = 'reachable';
    public const string STATUS_UNREACHABLE = 'unreachable';
    public const string STATUS_UNKNOWN = 'unknown';
    public const string STATUS_CHECKING = 'checking';

    protected $guarded = [];

    /**
     * Define the model values that shouldn't be audited.
     *
     * @var string[]
     */
    protected array $excludedFromAuditing = [
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user that owns the backup destination.
     *
     * @return BelongsTo<User, BackupDestination>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the backup tasks for the backup destination.
     *
     * @return HasMany<BackupTask>
     */
    public function backupTasks(): HasMany
    {
        return $this->hasMany(BackupTask::class);
    }

    /**
     * Get the human-readable type of the backup destination.
     */
    public function type(): ?string
    {
        return match ($this->type) {
            self::TYPE_S3 => 'S3',
            self::TYPE_CUSTOM_S3 => (string) trans('Custom S3'),
            self::TYPE_LOCAL => (string) trans('Local'),
            self::TYPE_DO_SPACES => (string) trans('DigitalOcean Spaces'),
            default => null,
        };
    }

    /**
     * Check if the backup destination is an S3 connection.
     */
    public function isS3Connection(): bool
    {
        return in_array($this->type, [self::TYPE_S3, self::TYPE_CUSTOM_S3, self::TYPE_DO_SPACES], true);
    }

    /**
     * Check if the backup destination is a local connection.
     */
    public function isLocalConnection(): bool
    {
        return $this->type === self::TYPE_LOCAL;
    }

    /**
     * Determine the S3 region for the backup destination.
     */
    public function determineS3Region(): string
    {
        if ($this->type === self::TYPE_CUSTOM_S3 && $this->custom_s3_region === null) {
            return 'us-east-1'; // Default region for custom S3
        }

        if ($this->type === self::TYPE_DO_SPACES && $this->custom_s3_region === null) {
            return 'us-east-1'; // Default region for DO S3
        }

        return (string) $this->custom_s3_region;
    }

    /**
     * Get an S3 client instance for the backup destination.
     *
     * @throws RuntimeException If the destination is not an S3 connection or client creation fails.
     */
    public function getS3Client(): S3Client
    {
        if (! $this->isS3Connection()) {
            throw new RuntimeException('Backup destination is not an S3 connection.');
        }

        $config = [
            'version' => 'latest',
            'use_path_style_endpoint' => $this->getAttributeValue('use_path_style_endpoint'),
            'region' => $this->determineS3Region(),
            'credentials' => [
                'key' => $this->s3_access_key,
                'secret' => $this->s3_secret_key,
            ],
        ];

        if ($this->type === self::TYPE_CUSTOM_S3 || $this->type === self::TYPE_DO_SPACES) {
            $config['endpoint'] = $this->custom_s3_endpoint;
        }

        try {
            return new S3Client($config);
        } catch (Exception $exception) {
            Log::error('Failed to create S3 client: ' . $exception->getMessage());
            throw new RuntimeException('Failed to create S3 client: ' . $exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Run the connection check for the backup destination.
     */
    public function run(): void
    {
        if ($this->isS3Connection()) {
            CheckBackupDestinationsS3ConnectionJob::dispatch($this)
                ->onQueue('connectivity-checks');
        }
    }

    /**
     * Check if the backup destination is reachable.
     */
    public function isReachable(): bool
    {
        return $this->status === self::STATUS_REACHABLE;
    }

    /**
     * Check if the backup destination is unreachable.
     */
    public function isUnreachable(): bool
    {
        return $this->status === self::STATUS_UNREACHABLE;
    }

    /**
     * Check if the backup destination status is unknown.
     */
    public function isUnknown(): bool
    {
        return $this->status === self::STATUS_UNKNOWN;
    }

    /**
     * Check if the backup destination is currently being checked.
     */
    public function isChecking(): bool
    {
        return $this->status === self::STATUS_CHECKING;
    }

    /**
     * Mark the backup destination as being checked.
     */
    public function markAsChecking(): void
    {
        $this->update(['status' => self::STATUS_CHECKING]);
    }

    /**
     * Mark the backup destination as reachable.
     */
    public function markAsReachable(): void
    {
        $this->update(['status' => self::STATUS_REACHABLE]);
    }

    /**
     * Mark the backup destination as unreachable.
     */
    public function markAsUnreachable(): void
    {
        $this->update(['status' => self::STATUS_UNREACHABLE]);
    }

    /**
     * Define the casts for the model's attributes.
     *
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            's3_access_key' => 'encrypted',
            's3_secret_key' => 'encrypted',
            's3_bucket_name' => 'encrypted',
            'custom_s3_endpoint' => 'encrypted',
        ];
    }
}
