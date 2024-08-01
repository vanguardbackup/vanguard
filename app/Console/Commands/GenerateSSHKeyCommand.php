<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Command to generate SSH keys for secure communication with remote servers.
 *
 * This command creates a new SSH key pair for Vanguard, used in remote server operations.
 * It includes checks for environment, existing keys, and required configuration.
 */
class GenerateSSHKeyCommand extends Command
{
    protected $signature = 'vanguard:generate-ssh-key';

    protected $description = 'This command will generate the SSH key needed for secure communication between Vanguard and remote servers.';

    /**
     * Execute the console command.
     *
     * Generates SSH keys after validating the environment, existing keys, and passphrase.
     */
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

    /**
     * Check if SSH keys already exist.
     *
     * @return bool True if either private or public key exists, false otherwise.
     */
    public function doSSHKeysAlreadyExist(): bool
    {
        $privateKeyPath = storage_path('app/ssh/key');
        $publicKeyPath = storage_path('app/ssh/key.pub');

        return file_exists($privateKeyPath) || file_exists($publicKeyPath);
    }

    /**
     * Check if the current environment is production.
     *
     * @return bool True if the environment is production, false otherwise.
     */
    public function appEnvironmentNotAllowed(): bool
    {
        return app()->environment('production');
    }

    /**
     * Check if the SSH passphrase is missing from the configuration.
     *
     * @return bool True if the passphrase is missing, false otherwise.
     */
    public function passphraseMissing(): bool
    {
        return ! config('app.ssh.passphrase');
    }

    /**
     * Generate SSH keys using ssh-keygen.
     *
     * This method creates the necessary directory, builds the ssh-keygen command,
     * and executes it to generate the SSH key pair.
     *
     * @throws RuntimeException If unable to create the SSH directory.
     */
    public function generateSSHKeys(): void
    {
        $path = storage_path('app/ssh');
        $privateKeyPath = $path . '/key';

        if (! file_exists($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }

        $comment = 'worker@' . str_replace(['http://', 'https://'], '', config('app.url'));

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
        } catch (ProcessFailedException $processFailedException) {
            $this->components->error('Failed to generate SSH keys.');
            Log::error('[SSH KEYS] Failed to generate SSH keys.', ['output' => $processFailedException->getMessage()]);
        }
    }
}
