<?php

declare(strict_types=1);

namespace App\Services\Backup;

/**
 * BackupConstants
 *
 * This class defines constants used throughout the backup process.
 * It includes definitions for file size limits, database types,
 * storage drivers, and retry settings.
 */
class BackupConstants
{
    /**
     * Maximum file size limit for backups in bytes.
     *
     * This constant defines the maximum allowed size for a backup file,
     * set to 50 GB (50 * 1024 * 1024 * 1024 bytes).
     */
    public const int|float FILE_SIZE_LIMIT = 50 * 1024 * 1024 * 1024; // 50 GB

    /**
     * Identifier for MySQL database type.
     */
    public const string DATABASE_TYPE_MYSQL = 'mysql';

    /**
     * Identifier for PostgreSQL database type.
     */
    public const string DATABASE_TYPE_POSTGRESQL = 'postgresql';

    /**
     * Identifier for Amazon S3 storage driver.
     */
    public const string DRIVER_S3 = 's3';

    /**
     * Identifier for custom S3-compatible storage driver.
     */
    public const string DRIVER_CUSTOM_S3 = 'custom_s3';

    /**
     * Identifier for local storage driver.
     */
    public const string DRIVER_LOCAL = 'local';

    /**
     * Maximum number of retry attempts for zip operations.
     */
    public const int ZIP_RETRY_MAX_ATTEMPTS = 3;

    /**
     * Delay in seconds between retry attempts for zip operations.
     */
    public const int ZIP_RETRY_DELAY_SECONDS = 5;
}
