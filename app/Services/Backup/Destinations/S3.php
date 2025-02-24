<?php

declare(strict_types=1);

namespace App\Services\Backup\Destinations;

use Override;
use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Destinations\Contracts\BackupDestinationInterface;
use App\Services\Backup\Traits\BackupHelpers;
use Aws\Api\DateTimeResult;
use Aws\S3\S3Client;
use DateTime;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;

/**
 * S3 Backup Destination
 *
 * This class implements the BackupDestinationInterface for managing backups on Amazon S3.
 * It provides functionality for listing, deleting, and streaming files to S3 storage.
 */
class S3 implements BackupDestinationInterface
{
    use BackupHelpers;

    /**
     * Constructor for S3 backup destination.
     *
     * @param  S3Client  $s3Client  The S3 client for AWS operations
     * @param  string  $bucketName  The name of the S3 bucket to use
     */
    public function __construct(
        protected S3Client $s3Client,
        protected string $bucketName
    ) {}

    /**
     * List files in the S3 bucket matching the given pattern.
     *
     * @param  string  $pattern  The pattern to match files against
     * @return array<string> An array of file keys matching the pattern
     */
    #[Override]
    public function listFiles(string $pattern): array
    {
        $result = $this->s3Client->listObjects([
            'Bucket' => $this->bucketName,
        ]);

        return $this->filterAndSortFiles($result['Contents'] ?? [], $pattern);
    }

    /**
     * Delete a file from the S3 bucket.
     *
     * @param  string  $filePath  The key of the file to delete
     */
    #[Override]
    public function deleteFile(string $filePath): void
    {
        $this->s3Client->deleteObject([
            'Bucket' => $this->bucketName,
            'Key' => $filePath,
        ]);
    }

    /**
     * Stream files from a remote location to the S3 bucket.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface to use for file transfer
     * @param  string  $remoteZipPath  The path of the remote zip file
     * @param  string  $fileName  The name of the file being transferred
     * @param  string|null  $storagePath  The storage path within the S3 bucket
     * @param  int  $retries  The number of retry attempts in case of failure (default: 3)
     * @param  int  $delay  The delay between retry attempts in seconds (default: 5)
     * @return bool True if the file was successfully streamed, false otherwise
     *
     * @throws FilesystemException If an error occurs during the file system operations
     */
    #[Override]
    public function streamFiles(
        SFTPInterface $sftp,
        string $remoteZipPath,
        string $fileName,
        ?string $storagePath = null,
        int $retries = 3,
        int $delay = 5
    ): bool {
        $fullPath = $this->getFullPath($fileName, $storagePath);

        $this->logStartStreaming($remoteZipPath, $fileName, $fullPath);

        return $this->retryCommand(
            fn (): bool => $this->performFileStreaming($sftp, $remoteZipPath, $fullPath),
            $retries,
            $delay
        );
    }

    /**
     * Get the full path (key) for a file in the S3 bucket.
     *
     * @param  string  $fileName  The name of the file
     * @param  string|null  $storagePath  An optional storage path within the bucket
     * @return string The full path (key) to the file in S3
     */
    public function getFullPath(string $fileName, ?string $storagePath): string
    {
        return $storagePath ? sprintf('%s/%s', $storagePath, $fileName) : $fileName;
    }

    /**
     * Create a Flysystem filesystem for S3 operations.
     *
     * @return Filesystem The created Flysystem filesystem
     */
    public function createS3Filesystem(): Filesystem
    {
        $awsS3V3Adapter = new AwsS3V3Adapter($this->s3Client, $this->bucketName);
        $filesystem = new Filesystem($awsS3V3Adapter);
        Log::debug('S3 filesystem created.');

        return $filesystem;
    }

    /**
     * Write a stream to S3 using Flysystem.
     *
     * @param  Filesystem  $filesystem  The Flysystem filesystem
     * @param  string  $fullPath  The full path (key) of the file in S3
     * @param  resource  $stream  The stream to write
     *
     * @throws FilesystemException If an error occurs during the write operation
     */
    public function writeStreamToS3(Filesystem $filesystem, string $fullPath, $stream): void
    {
        $filesystem->writeStream($fullPath, $stream);
        fclose($stream);
        Log::debug('Stream written to S3.', ['file_name' => $fullPath]);
    }

    /**
     * Log the start of file streaming to S3.
     *
     * @param  string  $remoteZipPath  The path of the remote zip file
     * @param  string  $fileName  The name of the file being streamed
     * @param  string  $fullPath  The full path (key) of the file in S3
     */
    public function logStartStreaming(string $remoteZipPath, string $fileName, string $fullPath): void
    {
        Log::info('Starting to stream file to S3.', [
            'remote_zip_path' => $remoteZipPath,
            'file_name' => $fileName,
            'full_path' => $fullPath,
        ]);
    }

    /**
     * Log successful file streaming to S3.
     *
     * @param  string  $fullPath  The full path (key) of the streamed file in S3
     */
    public function logSuccessfulStreaming(string $fullPath): void
    {
        Log::info('File successfully streamed to S3.', ['file_name' => $fullPath]);
    }

    /**
     * Filter and sort files based on a pattern.
     *
     * @param  array<int, array{Key: string, LastModified: DateTimeResult}>  $contents  The array of file contents from S3
     * @param  string  $pattern  The pattern to filter files
     * @return array<string> Filtered and sorted array of file keys
     */
    private function filterAndSortFiles(array $contents, string $pattern): array
    {
        return Collection::make($contents)
            ->filter(fn (array $file): bool => str_contains($file['Key'], $pattern))
            ->sortByDesc(fn (array $file): DateTime => $this->getLastModifiedDateTime($file))
            ->map(fn (array $file): string => $file['Key'])
            ->values()
            ->all();
    }

    /**
     * Get the last modified DateTime of a file.
     *
     * @param  array{LastModified: DateTimeResult}  $file  The file information from S3
     * @return DateTime The last modified DateTime of the file
     *
     * @throws Exception If unable to create DateTime object
     */
    private function getLastModifiedDateTime(array $file): DateTime
    {
        $dateTimeString = $file['LastModified']->format(DateTimeInterface::ATOM);

        return new DateTime($dateTimeString);
    }

    /**
     * Perform file streaming from SFTP to S3.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface
     * @param  string  $remoteZipPath  The path of the remote zip file
     * @param  string  $fullPath  The full path (key) of the file in S3
     * @return bool True if streaming was successful, false otherwise
     *
     * @throws FilesystemException If an error occurs during file system operations
     */
    private function performFileStreaming(SFTPInterface $sftp, string $remoteZipPath, string $fullPath): bool
    {
        $filesystem = $this->createS3Filesystem();
        $tempFile = $this->downloadFileViaSFTP($sftp, $remoteZipPath);
        $stream = $this->openFileAsStream($tempFile);

        $this->writeStreamToS3($filesystem, $fullPath, $stream);
        $this->cleanUpTempFile($tempFile);

        $this->logSuccessfulStreaming($fullPath);

        return true;
    }
}
