<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RemoteServer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class EncryptDatabasePasswordsCommand extends Command
{
    protected $signature = 'vanguard:encrypt-database-passwords';

    protected $description = 'Encrypts any unencrypted database passwords stored in the database.';

    protected int $newlyEncryptedCount = 0;

    public function handle(): void
    {
        $remoteServers = RemoteServer::all();

        if ($remoteServers->isEmpty()) {
            $this->components->error('No remote servers found.');

            return;
        }

        $remoteServers->each(function ($remoteServer) {

            if (! $remoteServer->isDatabasePasswordEncrypted()) {

                if (empty($remoteServer->database_password)) {
                    $this->components->warn("Database password for remote server {$remoteServer->label} is empty. Skipping encryption.");

                    return;
                }

                $remoteServer->update([
                    'database_password' => Crypt::encryptString($remoteServer->database_password),
                ]);

                $remoteServer->save();

                $this->newlyEncryptedCount++;
            }
        });

        $this->components->info("{$this->newlyEncryptedCount} database passwords have been encrypted.");

    }
}
