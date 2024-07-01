<?php

namespace App\Services\Backup;

use phpseclib3\Net\SFTP;

class SFTPAdapter implements SFTPInterface
{
    private SFTP $sftp;

    public function __construct(string $host, int $port = 22, int $timeout = 120)
    {
        $this->sftp = new SFTP($host, $port, $timeout);
    }

    public function login(string $username, ...$args): bool
    {
        return $this->sftp->login($username, ...$args);
    }

    public function getLastError(): string
    {
        return $this->sftp->getLastError();
    }

    public function exec($command): bool|string
    {
        return $this->sftp->exec($command);
    }

    public function isConnected(): bool
    {
        return $this->sftp->isConnected();
    }

    public function put($remote_file, $data, $mode = SFTP::SOURCE_STRING): bool
    {
        return $this->sftp->put($remote_file, $data, $mode);
    }

    public function get($remote_file, $local_file = false): bool|string
    {
        return $this->sftp->get($remote_file, $local_file);
    }

    public function delete($path, $recursive = true): bool
    {
        return $this->sftp->delete($path, $recursive);
    }

    public function mkdir($dir, $mode = -1, $recursive = false): bool
    {
        return $this->sftp->mkdir($dir, $mode, $recursive);
    }

    public function chmod($mode, $filename, $recursive = false): mixed
    {
        return $this->sftp->chmod($mode, $filename, $recursive);
    }
}
