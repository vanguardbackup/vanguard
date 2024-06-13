<?php

use App\Jobs\CheckBackupDestinationsS3ConnectionJob;
use App\Models\BackupDestination;

it('returns true if the backup destination is s3', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_S3,
    ]);

    $this->assertTrue($backupDestination->isS3Connection());
});

it('returns true if the backup destination is custom s3', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_CUSTOM_S3,
    ]);

    $this->assertTrue($backupDestination->isS3Connection());
});

it('returns false if the backup destination is not s3', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => 'local',
    ]);

    $this->assertFalse($backupDestination->isS3Connection());
});

it('throws an exception if the backup destination is not an s3 connection', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => 'local',
    ]);

    $backupDestination->getS3Client();
})->throws(RuntimeException::class, 'Backup destination is not an S3 connection.');

it('returns an s3 client if the backup destination is an aws s3 connection', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_S3,
        'custom_s3_region' => 'us-east-1',
        's3_access_key' => 'access_key_id',
        's3_secret_key' => 'secret_access_key',
    ]);

    $s3Client = $backupDestination->getS3Client();

    $this->assertEquals('2006-03-01', $s3Client->getApi()->getApiVersion());
    $this->assertEquals('us-east-1', $s3Client->getRegion());
});

it('returns an s3 client if the backup destination is a custom s3 connection', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_CUSTOM_S3,
        'custom_s3_region' => 'us-east-1',
        'custom_s3_endpoint' => 'http://localhost:9000',
        's3_access_key' => 'access_key_id',
        's3_secret_key' => 'secret_access_key',
    ]);

    $s3Client = $backupDestination->getS3Client();

    $this->assertEquals('2006-03-01', $s3Client->getApi()->getApiVersion());
    $this->assertEquals('us-east-1', $s3Client->getRegion());
    $this->assertEquals('http://localhost:9000', $s3Client->getEndpoint());
});

it('returns the type of the backup destination', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_S3,
    ]);

    $this->assertEquals('S3', $backupDestination->type());

    $backupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_CUSTOM_S3,
    ]);

    $this->assertEquals('Custom S3', $backupDestination->type());
});

it('returns a dummy region if the type is custom s3 and there isnt a region specified', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_CUSTOM_S3,
        'custom_s3_region' => null,
    ]);

    $this->assertEquals('us-east-1', $backupDestination->determineS3Region());
});

it('returns the custom s3 region if the type is custom s3', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_CUSTOM_S3,
        'custom_s3_region' => 'us-west-1',
    ]);

    $this->assertEquals('us-west-1', $backupDestination->determineS3Region());
});

it('returns the custom s3 region if the type is s3', function () {

    $backupDestination = BackupDestination::factory()->create([
        'type' => BackupDestination::TYPE_S3,
        'custom_s3_region' => 'us-west-1',
    ]);

    $this->assertEquals('us-west-1', $backupDestination->determineS3Region());
});

it('runs the s3 connectivity check job', function () {
    Queue::fake();

    $backupDestination = BackupDestination::factory()->create();

    $backupDestination->run();

    Queue::assertPushed(CheckBackupDestinationsS3ConnectionJob::class, function ($job) use ($backupDestination) {
        return $job->backupDestination->is($backupDestination);
    });
});

it('returns true if the backup destination is reachable and status is reachable', function () {

    $backupDestination = BackupDestination::factory()->reachable()->create();

    $this->assertTrue($backupDestination->isReachable());
});

it('returns false if the backup destination is not reachable and status is reachable', function () {

    $backupDestination = BackupDestination::factory()->unreachable()->create();

    $this->assertFalse($backupDestination->isReachable());
});

it('returns false if the backup destination is not reachable and status is unreachable', function () {

    $backupDestination = BackupDestination::factory()->unreachable()->create();

    $this->assertFalse($backupDestination->isReachable());
});

it('returns false if the backup destination is reachable and status is unreachable', function () {

    $backupDestination = BackupDestination::factory()->reachable()->create();

    $this->assertTrue($backupDestination->isReachable());
});

it('returns false if the backup destination is not reachable and status is unknown', function () {

    $backupDestination = BackupDestination::factory()->unknown()->create();

    $this->assertFalse($backupDestination->isReachable());
});

it('returns false if the backup destination is reachable and status is unknown', function () {

    $backupDestination = BackupDestination::factory()->reachable()->create();

    $this->assertTrue($backupDestination->isReachable());
});

it('returns false if the backup destination is unknown and status is unreachable', function () {

    $backupDestination = BackupDestination::factory()->unknown()->create();

    $this->assertFalse($backupDestination->isReachable());
});

it('returns false if the backup destination is unknown and status is reachable', function () {

    $backupDestination = BackupDestination::factory()->unknown()->create();

    $this->assertFalse($backupDestination->isReachable());
});

it('returns false if the backup destination is unknown and status is unknown', function () {

    $backupDestination = BackupDestination::factory()->unknown()->create();

    $this->assertFalse($backupDestination->isReachable());
});

it('returns false if the backup destination is checking and status is unknown', function () {

    $backupDestination = BackupDestination::factory()->checking()->create();

    $this->assertFalse($backupDestination->isReachable());
});

it('returns false if the backup destination is checking and status is reachable', function () {

    $backupDestination = BackupDestination::factory()->checking()->create();

    $this->assertFalse($backupDestination->isReachable());
});

it('returns false if the backup destination is checking and status is unreachable', function () {

    $backupDestination = BackupDestination::factory()->checking()->create();

    $this->assertFalse($backupDestination->isReachable());
});

it('returns true if the backup destination is checking and status is checking', function () {

    $backupDestination = BackupDestination::factory()->checking()->create();

    $this->assertTrue($backupDestination->isChecking());
});

it('returns false if the backup destination is not checking and status is checking', function () {

    $backupDestination = BackupDestination::factory()->reachable()->create();

    $this->assertFalse($backupDestination->isChecking());
});

it('sets the status to checking', function () {

    $backupDestination = BackupDestination::factory()->create();

    $backupDestination->markAsChecking();

    $this->assertEquals(BackupDestination::STATUS_CHECKING, $backupDestination->status);
});

it('sets the status to reachable', function () {

    $backupDestination = BackupDestination::factory()->create();

    $backupDestination->markAsReachable();

    $this->assertEquals(BackupDestination::STATUS_REACHABLE, $backupDestination->status);
});

it('sets the status to unreachable', function () {

    $backupDestination = BackupDestination::factory()->create();

    $backupDestination->markAsUnreachable();

    $this->assertEquals(BackupDestination::STATUS_UNREACHABLE, $backupDestination->status);
});
