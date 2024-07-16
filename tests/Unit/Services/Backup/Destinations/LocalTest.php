<?php

declare(strict_types=1);
use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Destinations\Local;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    $this->mockSftp = Mockery::mock(SFTPInterface::class);
    $this->basePath = '/remote/base/path';
    $this->local = new Local($this->mockSftp, $this->basePath);
});

afterEach(function (): void {
    Mockery::close();
});

describe('Local driver', function (): void {
    it('lists files', function (): void {
        $mockFiles = [
            '/remote/base/path/file1.txt',
            '/remote/base/path/file2.txt',
            '/remote/base/path/subdir/file3.txt',
        ];

        $this->mockSftp->shouldReceive('exec')
            ->with(sprintf('find %s -type f', $this->basePath))
            ->andReturn(implode("\n", $mockFiles));

        $this->mockSftp->shouldReceive('stat')
            ->andReturn(['mtime' => Carbon::now()->timestamp]);

        $result = $this->local->listFiles('file');

        expect($result)->toHaveCount(3)
            ->and($result)->toContain('file1.txt')
            ->and($result)->toContain('file2.txt')
            ->and($result)->toContain('subdir/file3.txt');
    });

    it('deletes a file', function (): void {
        $this->mockSftp->shouldReceive('delete')
            ->with('/remote/base/path/file1.txt')
            ->andReturn(true)
            ->once();

        Log::shouldReceive('info')
            ->with('File deleted from remote local storage.', ['file_path' => '/remote/base/path/file1.txt'])
            ->once();

        $this->local->deleteFile('file1.txt');
    });

    describe('file streaming', function (): void {
        beforeEach(function (): void {
            $this->mockSourceSftp = Mockery::mock(SFTPInterface::class);
            $this->mockLocal = Mockery::mock(Local::class, [$this->mockSftp, $this->basePath])->makePartial();
            $this->mockLocal->shouldAllowMockingProtectedMethods();
            $this->mockLocal->shouldReceive('downloadFileViaSFTP')->andReturn('/tmp/tempfile');
            $this->mockLocal->shouldReceive('cleanUpTempFile');
            $this->mockContent = 'file content';
            $this->mockLocal->shouldReceive('getFileContents')->andReturn($this->mockContent);
            $this->mockLocal->shouldReceive('ensureDirectoryExists')->andReturn(true);
            $this->mockSftp->shouldReceive('mkdir')->andReturn(true);
            $this->mockSftp->shouldReceive('getLastError')->andReturn('');
            $this->mockSftp->shouldReceive('exec')->withArgs(fn ($command): bool => str_starts_with($command, 'ls -la '))->andReturn('');
        });

        it('streams files successfully', function (): void {
            $this->mockSftp->shouldReceive('put')
                ->with('/remote/base/path/storage/path/file.zip', $this->mockContent)
                ->andReturn(true);

            $result = $this->mockLocal->streamFiles($this->mockSourceSftp, '/remote/source/path', 'file.zip', 'storage/path');

            expect($result)->toBeTrue();
        });

        it('handles file streaming failure', function (): void {
            $this->mockSftp->shouldReceive('put')->andReturn(false);

            Log::shouldReceive('info');
            Log::shouldReceive('warning')->atLeast()->once()->withAnyArgs();
            Log::shouldReceive('error')->atLeast()->once()->withArgs(fn ($message): bool => str_contains($message, 'Failed to stream file to remote local storage'));

            $result = $this->mockLocal->streamFiles($this->mockSourceSftp, '/remote/source/path', 'file.zip', 'storage/path');

            expect($result)->toBeFalse();
        });

        it('ensures directory exists', function (): void {
            $method = new ReflectionMethod(Local::class, 'ensureDirectoryExists');

            $this->mockSftp->shouldReceive('mkdir')
                ->with('/remote/base/path/new/directory', 0755, true)
                ->andReturn(true);

            Log::shouldReceive('info')
                ->with('Directory created successfully', ['path' => '/remote/base/path/new/directory'])
                ->once();

            $result = $method->invokeArgs($this->local, ['/remote/base/path/new/directory']);

            expect($result)->toBeTrue();
        });
    });

    it('gets full path', function (): void {
        expect($this->local->getFullPath('file.txt', null))->toBe('/remote/base/path/file.txt')
            ->and($this->local->getFullPath('file.txt', 'storage/path'))->toBe('/remote/base/path/storage/path/file.txt');
    });

    describe('logging', function (): void {
        it('logs start of streaming', function (): void {
            $method = new ReflectionMethod(Local::class, 'logStartStreaming');

            Log::shouldReceive('info')
                ->with('Starting to stream file to remote local storage.', Mockery::type('array'))
                ->once();

            $method->invokeArgs($this->local, ['/remote/source/path', 'file.txt', '/remote/base/path/storage/path/file.txt']);
        });

        it('logs successful streaming', function (): void {
            $method = new ReflectionMethod(Local::class, 'logSuccessfulStreaming');

            Log::shouldReceive('info')
                ->with('File successfully streamed to remote local storage.', Mockery::type('array'))
                ->once();

            $method->invokeArgs($this->local, ['/remote/base/path/file.txt']);
        });
    });

    it('filters and sorts files correctly', function (): void {
        $method = new ReflectionMethod(Local::class, 'filterAndSortFiles');

        $files = [
            '/remote/base/path/file1.txt',
            '/remote/base/path/file2.log',
            '/remote/base/path/subdir/file3.txt',
        ];

        $this->mockSftp->shouldReceive('stat')
            ->andReturn(['mtime' => Carbon::now()->timestamp]);

        $result = $method->invokeArgs($this->local, [$files, '.txt']);

        expect($result)->toHaveCount(2)
            ->and($result)->toContain('file1.txt')
            ->and($result)->toContain('subdir/file3.txt');
    });

    it('gets last modified date time', function (): void {
        $method = new ReflectionMethod(Local::class, 'getLastModifiedDateTime');

        $this->mockSftp->shouldReceive('stat')
            ->with('/remote/base/path/file.txt')
            ->andReturn(['mtime' => 1625097600]); // July 1, 2021 00:00:00 UTC

        $result = $method->invokeArgs($this->local, ['/remote/base/path/file.txt']);

        expect($result)->toBeInstanceOf(DateTime::class)
            ->and($result->format('Y-m-d H:i:s'))->toBe('2021-07-01 00:00:00');
    });

    it('lists directory contents', function (): void {
        $method = new ReflectionMethod(Local::class, 'listDirectoryContents');

        $this->mockSftp->shouldReceive('exec')
            ->with(sprintf('find %s -type f', $this->basePath))
            ->andReturn("/remote/base/path/file1.txt\n/remote/base/path/file2.txt");

        $result = $method->invokeArgs($this->local, [$this->basePath]);

        expect($result)->toBe(['/remote/base/path/file1.txt', '/remote/base/path/file2.txt']);
    });
});
