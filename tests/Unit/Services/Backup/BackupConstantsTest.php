<?php

declare(strict_types=1);

use App\Services\Backup\BackupConstants;

it('has correct file size limit', function (): void {
    expect(BackupConstants::FILE_SIZE_LIMIT)->toBe(50 * 1024 * 1024 * 1024);
});

it('has correct database types', function (): void {
    expect(BackupConstants::DATABASE_TYPE_MYSQL)->toBe('mysql')
        ->and(BackupConstants::DATABASE_TYPE_POSTGRESQL)->toBe('postgresql');
});

it('has correct drivers', function (): void {
    expect(BackupConstants::DRIVER_S3)->toBe('s3')
        ->and(BackupConstants::DRIVER_CUSTOM_S3)->toBe('custom_s3');
});

it('has correct zip retry constants', function (): void {
    expect(BackupConstants::ZIP_RETRY_MAX_ATTEMPTS)->toBe(3)
        ->and(BackupConstants::ZIP_RETRY_DELAY_SECONDS)->toBe(5);
});
