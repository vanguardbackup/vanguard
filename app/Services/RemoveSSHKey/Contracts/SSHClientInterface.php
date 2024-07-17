<?php

declare(strict_types=1);

namespace App\Services\RemoveSSHKey\Contracts;

interface SSHClientInterface
{
    public function connect(string $host, int $port, string $username, string $privateKey): bool;

    public function executeCommand(string $command): string;
}
