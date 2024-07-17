<?php

declare(strict_types=1);

namespace App\Services\RemoveSSHKey\Contracts;

use App\Models\RemoteServer;

interface KeyRemovalNotifierInterface
{
    public function notifySuccess(RemoteServer $remoteServer): void;

    public function notifyFailure(RemoteServer $remoteServer, string $errorMessage): void;
}
