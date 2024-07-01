<?php

namespace App\Services\Backup;

use phpseclib3\Net\SFTP;

interface SFTPInterface
{
    public function __construct(string $host, int $port = 22, int $timeout = 120);

    /**
     * @param  mixed  ...$args
     */
    public function login(string $username, ...$args): bool;

    public function getLastError();

    public function exec($command);

    public function isConnected();

    public function put($remote_file, $data, $mode = SFTP::SOURCE_STRING);

    public function get($remote_file, $local_file = false);

    public function delete($path, $recursive = true);

    public function mkdir($dir, $mode = -1, $recursive = false);

    public function chmod($mode, $filename, $recursive = false);
}
