<?php

declare(strict_types=1);

namespace App\Services\RemoveSSHKey\Contracts;

interface SSHKeyProviderInterface
{
    public function getPrivateKey(): string;

    public function getPublicKey(): string;
}
