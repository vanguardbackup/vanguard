<?php

declare(strict_types=1);

use App\Actions\BackupDestinations\CheckS3Connection;
use App\Models\BackupDestination;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;

it('returns false and logs a message if the backup destination is not an S3 connection', function () {
    Event::fake();
    $backupDestination = Mockery::mock(BackupDestination::class);
    $backupDestination->shouldReceive('isS3Connection')->andReturn(false);

    Log::shouldReceive('info')->once()->with('[S3] Backup destination is not an S3 connection. Skipping.');

    $action = new CheckS3Connection;
    $result = $action->handle($backupDestination);

    expect($result)->toBeFalse();
});

it('returns true if the S3 connection is successful', function () {
    Event::fake();
    $backupDestination = Mockery::mock(BackupDestination::class);
    $backupDestination->shouldReceive('isS3Connection')->once()->andReturnTrue();

    $s3Client = Mockery::mock(S3Client::class);
    $s3Client->shouldReceive('listBuckets')->andReturn(true);
    $backupDestination->shouldReceive('getS3Client')->once()->andReturn($s3Client);
    $backupDestination->shouldReceive('markAsReachable')->once();

    $action = new CheckS3Connection;
    $result = $action->handle($backupDestination);

    expect($result)->toBeTrue();
});

it('returns false and logs an error if listing S3 buckets fails', function () {
    Event::fake();
    $backupDestination = Mockery::mock(BackupDestination::class);
    $backupDestination->shouldReceive('isS3Connection')->andReturn(true);

    $s3Client = Mockery::mock(S3Client::class);
    $s3Client->shouldReceive('listBuckets')->andThrow(new Exception('AWS error'));
    $backupDestination->shouldReceive('getS3Client')->andReturn($s3Client);

    Log::shouldReceive('error')->once()->with('[S3] Failed to list buckets: AWS error');
    $backupDestination->shouldReceive('markAsUnreachable')->once();

    $action = new CheckS3Connection;
    $result = $action->handle($backupDestination);

    expect($result)->toBeFalse();
});
