<?php

namespace App\Services\Backup\Contracts;

use phpseclib3\Net\SFTP;

interface SFTPInterface
{
    public function __construct(string $host, int $port = 22, int $timeout = 120);

    /**
     * @param  mixed  ...$args
     */
    public function login(string $username, ...$args): bool;

    public function getLastError(): string;

    public function exec(string $command): bool|string;

    public function isConnected(): bool;

    public function put(string $remote_file, string $data, int $mode = SFTP::SOURCE_STRING): bool;

    public function get(string $remote_file, string|false $local_file = false): bool|string;

    public function delete(string $path, bool $recursive = true): bool;

    public function mkdir(string $dir, int $mode = -1, bool $recursive = false): bool;

    public function chmod(int $mode, string $filename, bool $recursive = false): mixed;

    /**
     * @return array<string, mixed>|false
     */
    public function stat(string $filename): array|false;
}
