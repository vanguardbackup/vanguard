<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GenerateSSHKeyCommand extends Command
{
    protected $signature = 'vanguard:generate-ssh-key';

    protected $description = 'This command will generate the SSH key needed for secure communication between Vanguard and remote servers.';

    public function handle(): void
    {
        if ($this->appEnvironmentNotAllowed()) {
            $this->components->error('Cannot generate SSH keys in a production environment.');

            return;
        }

        if ($this->doSSHKeysAlreadyExist()) {
            $this->components->error('SSH keys already exist. Cannot generate new keys.');

            return;
        }

        if ($this->passphraseMissing()) {
            $this->components->error('SSH Passphrase is missing. Please set the SSH_PASSPHRASE environment variable.');

            return;
        }

        $this->generateSSHKeys();
    }

    public function doSSHKeysAlreadyExist(): bool
    {
        $privateKeyPath = storage_path('app/ssh/key');
        $publicKeyPath = storage_path('app/ssh/key.pub');

        return file_exists($privateKeyPath) || file_exists($publicKeyPath);
    }

    public function appEnvironmentNotAllowed(): bool
    {
        return app()->environment('production');
    }

    public function passphraseMissing(): bool
    {
        return ! config('app.ssh.passphrase');
    }

    public function generateSSHKeys(): void
    {
        $path = storage_path('app/ssh');
        $privateKeyPath = "{$path}/key";

        if (! file_exists($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }

        $comment = 'worker@'.str_replace(['http://', 'https://'], '', config('app.url'));

        $command = [
            'ssh-keygen',
            '-t', 'rsa',
            '-b', '4096',
            '-f', $privateKeyPath,
            '-N', config('app.ssh.passphrase'),
            '-C', $comment,
        ];

        $process = new Process($command);

        try {
            $process->mustRun();
            Log::info('[SSH KEYS] SSH keys generated successfully.');
            $this->components->info('SSH keys generated successfully.');
        } catch (ProcessFailedException $exception) {
            $this->components->error('Failed to generate SSH keys.');
            Log::error('[SSH KEYS] Failed to generate SSH keys.', ['output' => $exception->getMessage()]);
        }
    }
}
