<?php

use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Destinations\S3;
use Aws\Api\DateTimeResult;
use Aws\S3\S3Client;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToCreateDirectory;

beforeEach(function () {
    $this->s3Client = Mockery::mock(S3Client::class);
    $this->bucketName = 'test-bucket';
    $this->s3 = new S3($this->s3Client, $this->bucketName);
});

afterEach(function () {
    Mockery::close();
});

it('lists files', function () {
    $mockContents = [
        ['Key' => 'file1.txt', 'LastModified' => new DateTimeResult('2023-01-01T00:00:00Z')],
        ['Key' => 'file2.txt', 'LastModified' => new DateTimeResult('2023-01-02T00:00:00Z')],
    ];

    $this->s3Client->shouldReceive('listObjects')
        ->with(['Bucket' => $this->bucketName])
        ->andReturn(['Contents' => $mockContents]);

    $result = $this->s3->listFiles('file');

    expect($result)->toBe(['file2.txt', 'file1.txt']);
});

it('deletes a file', function () {
    $this->s3Client->shouldReceive('deleteObject')
        ->with([
            'Bucket' => $this->bucketName,
            'Key' => 'file1.txt',
        ])
        ->once();

    $this->s3->deleteFile('file1.txt');
});

it('streams files successfully', function () {
    $mockSftp = Mockery::mock(SFTPInterface::class);
    $mockFilesystem = Mockery::mock(Filesystem::class);

    // Mock the S3 instance
    $mockS3 = Mockery::mock(S3::class, [$this->s3Client, $this->bucketName])->makePartial();
    $mockS3->shouldReceive('createS3Filesystem')->andReturn($mockFilesystem);
    $mockS3->shouldAllowMockingProtectedMethods();
    $mockS3->shouldReceive('downloadFileViaSFTP')->andReturn('/tmp/tempfile');
    $mockS3->shouldReceive('openFileAsStream')->andReturn(fopen('php://memory', 'rb+'));
    $mockS3->shouldReceive('writeStreamToS3');
    $mockS3->shouldReceive('cleanUpTempFile');

    $result = $mockS3->streamFiles($mockSftp, '/remote/path', 'file.zip', 'storage/path');

    expect($result)->toBeTrue();
});

it('handles file streaming failure', function () {
    $mockSftp = Mockery::mock(SFTPInterface::class);

    // Mock the S3 instance
    $mockS3 = Mockery::mock(S3::class, [$this->s3Client, $this->bucketName])->makePartial();
    $mockS3->shouldReceive('createS3Filesystem')->andThrow(new UnableToCreateDirectory('Failed to create filesystem'));

    $result = $mockS3->streamFiles($mockSftp, '/remote/path', 'file.zip', 'storage/path');

    expect($result)->toBeFalse();
})->throws(UnableToCreateDirectory::class, 'Failed to create filesystem');

it('gets full path', function () {
    $result1 = $this->s3->getFullPath('file.txt', null);
    $result2 = $this->s3->getFullPath('file.txt', 'storage/path');

    expect($result1)->toBe('file.txt')
        ->and($result2)->toBe('storage/path/file.txt');
});

// For the following tests, we need to make these methods public in the S3 class
// or use reflection to test them. Here's an example using reflection:

it('creates S3 filesystem', function () {
    $method = new ReflectionMethod(S3::class, 'createS3Filesystem');

    $filesystem = $method->invoke($this->s3);

    expect($filesystem)->toBeInstanceOf(Filesystem::class);
});

it('writes stream to S3', function () {
    $mockFilesystem = Mockery::mock(Filesystem::class);
    $mockStream = fopen('php://memory', 'r+');

    $mockFilesystem->shouldReceive('writeStream')
        ->with('path/to/file.txt', $mockStream)
        ->once();

    $method = new ReflectionMethod(S3::class, 'writeStreamToS3');

    $method->invokeArgs($this->s3, [$mockFilesystem, 'path/to/file.txt', $mockStream]);

    expect(true)->toBeTrue(); // If we get here without exceptions, the test passes
});

it('logs start of streaming', function () {
    $method = new ReflectionMethod(S3::class, 'logStartStreaming');

    $method->invokeArgs($this->s3, ['/remote/path', 'file.txt', 'storage/path/file.txt']);

    // If we get here without exceptions, the test passes
    expect(true)->toBeTrue();
});

it('logs successful streaming', function () {
    $method = new ReflectionMethod(S3::class, 'logSuccessfulStreaming');

    $method->invokeArgs($this->s3, ['storage/path/file.txt']);

    // If we get here without exceptions, the test passes
    expect(true)->toBeTrue();
});
