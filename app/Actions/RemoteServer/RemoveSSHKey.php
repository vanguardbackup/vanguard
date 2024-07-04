<?php

declare(strict_types=1);

namespace App\Actions\RemoteServer;

use App\Mail\RemoteServers\FailedToRemoveKey;
use App\Mail\RemoteServers\SuccessfullyRemovedKey;
use App\Models\RemoteServer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use RuntimeException;

class RemoveSSHKey
{
    public function handle(RemoteServer $remoteServer): void
    {
        Log::info('Removing SSH key from server.', ['server_id' => $remoteServer->getAttribute('id')]);

        /** @var PrivateKey $key */
        $key = PublicKeyLoader::load(get_ssh_private_key(), config('app.ssh.passphrase'));

        try {
            $ssh = new SSH2($remoteServer->getAttribute('ip_address'), (int) $remoteServer->getAttribute('port'), 5);

            $ssh->login($remoteServer->getAttribute('username'), $key);

            $vanguardsPublicKey = get_ssh_public_key();

            $ssh->exec("sed -i '/{$vanguardsPublicKey}/d' ~/.ssh/authorized_keys");

            Log::info('Removed SSH key from server.', ['server_id' => $remoteServer->getAttribute('id')]);
            Log::info('Updated server to indicate SSH key was removed.', ['server_id' => $remoteServer->getAttribute('id')]);
            Mail::to($remoteServer->getAttribute('user')->email)->queue(new SuccessfullyRemovedKey($remoteServer));

        } catch (RuntimeException $e) {
            Log::debug('[SSH Key Removal] Failed to connect to remote server', ['error' => $e->getMessage()]);
            Mail::to($remoteServer->getAttribute('user')->email)->queue(new FailedToRemoveKey($remoteServer, $e->getMessage()));
        }
    }
}
