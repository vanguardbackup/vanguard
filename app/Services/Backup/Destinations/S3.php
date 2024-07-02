<?php

declare(strict_types=1);

namespace App\Services\Backup\Destinations;

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

class S3 implements BackupDestinationInterface
{
    use BackupHelpers;

    public function __construct(
        protected S3Client $client,
        protected string $bucketName
    ) {}

    /**
     * @return array<string>
     */
    public function listFiles(string $pattern): array
    {
        $result = $this->client->listObjects([
            'Bucket' => $this->bucketName,
        ]);

        return $this->filterAndSortFiles($result['Contents'] ?? [], $pattern);
    }

    public function deleteFile(string $filePath): void
    {
        $this->client->deleteObject([
            'Bucket' => $this->bucketName,
            'Key' => $filePath,
        ]);
    }

    /**
     * @throws FilesystemException
     */
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
            fn () => $this->performFileStreaming($sftp, $remoteZipPath, $fullPath),
            $retries,
            $delay
        );
    }

    /**
     * @param  array<int, array{Key: string, LastModified: DateTimeResult}>  $contents
     * @return array<string>
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
     * @param  array{LastModified: DateTimeResult}  $file
     *
     * @throws Exception
     */
    private function getLastModifiedDateTime(array $file): DateTime
    {
        $dateTimeString = $file['LastModified']->format(DateTimeInterface::ATOM);

        return new DateTime($dateTimeString);
    }

    public function getFullPath(string $fileName, ?string $storagePath): string
    {
        return $storagePath ? "{$storagePath}/{$fileName}" : $fileName;
    }

    /**
     * @throws FilesystemException
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

    public function createS3Filesystem(): Filesystem
    {
        $adapter = new AwsS3V3Adapter($this->client, $this->bucketName);
        $filesystem = new Filesystem($adapter);
        Log::debug('S3 filesystem created.');

        return $filesystem;
    }

    /**
     * @param  resource  $stream
     *
     * @throws FilesystemException
     */
    public function writeStreamToS3(Filesystem $filesystem, string $fullPath, $stream): void
    {
        $filesystem->writeStream($fullPath, $stream);
        fclose($stream);
        Log::debug('Stream written to S3.', ['file_name' => $fullPath]);
    }

    public function logStartStreaming(string $remoteZipPath, string $fileName, string $fullPath): void
    {
        Log::info('Starting to stream file to S3.', [
            'remote_zip_path' => $remoteZipPath,
            'file_name' => $fileName,
            'full_path' => $fullPath,
        ]);
    }

    public function logSuccessfulStreaming(string $fullPath): void
    {
        Log::info('File successfully streamed to S3.', ['file_name' => $fullPath]);
    }
}
