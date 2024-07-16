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

        /** @var PrivateKey $asymmetricKey */
        $asymmetricKey = PublicKeyLoader::load(get_ssh_private_key(), config('app.ssh.passphrase'));

        try {
            $ssh2 = new SSH2($remoteServer->getAttribute('ip_address'), (int) $remoteServer->getAttribute('port'), 5);

            $ssh2->login($remoteServer->getAttribute('username'), $asymmetricKey);

            $vanguardsPublicKey = get_ssh_public_key();

            $ssh2->exec(sprintf("sed -i '/%s/d' ~/.ssh/authorized_keys", $vanguardsPublicKey));

            Log::info('Removed SSH key from server.', ['server_id' => $remoteServer->getAttribute('id')]);
            Log::info('Updated server to indicate SSH key was removed.', ['server_id' => $remoteServer->getAttribute('id')]);
            Mail::to($remoteServer->getAttribute('user')->email)->queue(new SuccessfullyRemovedKey($remoteServer));

        } catch (RuntimeException $runtimeException) {
            Log::debug('[SSH Key Removal] Failed to connect to remote server', ['error' => $runtimeException->getMessage()]);
            Mail::to($remoteServer->getAttribute('user')->email)->queue(new FailedToRemoveKey($remoteServer, $runtimeException->getMessage()));
        }
    }
}
