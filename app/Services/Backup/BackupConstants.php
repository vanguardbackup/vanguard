<?php

declare(strict_types=1);

namespace App\Services\Backup;

class BackupConstants
{
    public const int|float FILE_SIZE_LIMIT = 50 * 1024 * 1024 * 1024; // 50 GB

    public const string DATABASE_TYPE_MYSQL = 'mysql';

    public const string DATABASE_TYPE_POSTGRESQL = 'postgresql';

    public const string DRIVER_S3 = 's3';

    public const string DRIVER_CUSTOM_S3 = 'custom_s3';

    public const string DRIVER_LOCAL = 'local';

    public const int ZIP_RETRY_MAX_ATTEMPTS = 3;

    public const int ZIP_RETRY_DELAY_SECONDS = 5;
}
