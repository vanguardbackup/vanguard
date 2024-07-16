<?php

declare(strict_types=1);

use App\Actions\BackupDestinations\CheckS3Connection;
use App\Models\BackupDestination;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;

it('returns false and logs a message if the backup destination is not an S3 connection', function (): void {
    Event::fake();
    $mock = Mockery::mock(BackupDestination::class);
    $mock->shouldReceive('isS3Connection')->andReturn(false);

    Log::shouldReceive('info')->once()->with('[S3] Backup destination is not an S3 connection. Skipping.');

    $action = new CheckS3Connection;
    $result = $action->handle($mock);

    expect($result)->toBeFalse();
});

it('returns true if the S3 connection is successful', function (): void {
    Event::fake();
    $mock = Mockery::mock(BackupDestination::class);
    $mock->shouldReceive('isS3Connection')->once()->andReturnTrue();

    $s3Client = Mockery::mock(S3Client::class);
    $s3Client->shouldReceive('listBuckets')->andReturn(true);
    $mock->shouldReceive('getS3Client')->once()->andReturn($s3Client);
    $mock->shouldReceive('markAsReachable')->once();

    $action = new CheckS3Connection;
    $result = $action->handle($mock);

    expect($result)->toBeTrue();
});

it('returns false and logs an error if listing S3 buckets fails', function (): void {
    Event::fake();
    $mock = Mockery::mock(BackupDestination::class);
    $mock->shouldReceive('isS3Connection')->andReturn(true);

    $s3Client = Mockery::mock(S3Client::class);
    $s3Client->shouldReceive('listBuckets')->andThrow(new Exception('AWS error'));
    $mock->shouldReceive('getS3Client')->andReturn($s3Client);

    Log::shouldReceive('error')->once()->with('[S3] Failed to list buckets: AWS error');
    $mock->shouldReceive('markAsUnreachable')->once();

    $action = new CheckS3Connection;
    $result = $action->handle($mock);

    expect($result)->toBeFalse();
});
