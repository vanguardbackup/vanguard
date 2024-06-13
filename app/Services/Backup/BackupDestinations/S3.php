<?php

namespace App\Services\Backup\BackupDestinations;

use App\Services\Backup\Contracts\BackupDestinationInterface;
use App\Services\Backup\Traits\BackupHelpers;
use Illuminate\Support\Facades\Log;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use phpseclib3\Net\SFTP;

class S3 implements BackupDestinationInterface
{
    use BackupHelpers;

    protected $client;

    protected $bucketName;

    public function __construct($client, $bucketName)
    {
        $this->client = $client;
        $this->bucketName = $bucketName;
    }

    public function listFiles(string $pattern): array
    {
        $result = $this->client->listObjects([
            'Bucket' => $this->bucketName,
        ]);

        return collect($result['Contents'])
            ->filter(function ($file) use ($pattern) {
                return str_contains($file['Key'], $pattern);
            })
            ->sortByDesc('LastModified')
            ->toArray();
    }

    public function deleteFile(string $filePath): void
    {
        $this->client->deleteObject([
            'Bucket' => $this->bucketName,
            'Key' => $filePath,
        ]);
    }

    public function streamFiles(SFTP $sftp, string $remoteZipPath, string $fileName, string $storagePath, int $retries = 3, int $delay = 5): bool
    {
        Log::info('Starting to stream file to S3.', ['remote_zip_path' => $remoteZipPath, 'file_name' => $fileName, 'storage_path' => $storagePath]);

        return $this->retryCommand(function () use ($sftp, $remoteZipPath, $fileName, $storagePath) {
            $adapter = new AwsS3V3Adapter($this->client, $this->bucketName);
            $filesystem = new Filesystem($adapter);
            Log::debug('S3 filesystem created.');

            $tempFile = $this->downloadFileViaSFTP($sftp, $remoteZipPath);
            $stream = $this->openFileAsStream($tempFile);

            // Use the storage path and file name to create the full path for the file in the S3 bucket
            $fullPath = $storagePath.'/'.$fileName;

            $filesystem->writeStream($fullPath, $stream);
            fclose($stream);
            Log::debug('Stream written to S3.', ['file_name' => $fullPath]);

            $this->cleanUpTempFile($tempFile);

            Log::info('File successfully streamed to S3.', ['file_name' => $fullPath]);

            return true;
        }, $retries, $delay);
    }
}
