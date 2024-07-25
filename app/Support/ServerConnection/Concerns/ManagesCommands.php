<?php

declare(strict_types=1);

namespace App\Support\ServerConnection\Concerns;

use App\Support\ServerConnection\Exceptions\ConnectionException;

/**
 * Trait ManagesCommands
 *
 * This trait provides methods for executing commands on a remote server.
 */
trait ManagesCommands
{
    /**
     * Run a command on the server.
     *
     * @param  string  $command  The command to run
     * @return string The command output
     *
     * @throws ConnectionException If the connection is not established or command execution fails
     */
    public function run(string $command): string
    {
        $this->ensureConnected();

        $output = $this->connection->exec($command);

        if ($output === false) {
            throw ConnectionException::withMessage('Failed to execute command: ' . $command);
        }

        return $output;
    }

    /**
     * Run a command on the server and stream the output.
     *
     * @param  string  $command  The command to run
     * @param  callable  $callback  The callback to handle streamed output
     *
     * @throws ConnectionException If the connection is not established
     */
    public function runStream(string $command, callable $callback): void
    {
        $this->ensureConnected();

        $this->connection->exec($command, function ($stream) use ($callback): void {
            $buffer = '';
            while ($buffer = fgets($stream)) {
                $callback($buffer);
            }
        });
    }

    /**
     * Ensure that the connection is established before performing an operation.
     *
     * @throws ConnectionException If the connection is not established
     */
    private function ensureConnected(): void
    {
        if (! $this->isConnected()) {
            throw ConnectionException::withMessage('No active connection. Please connect first.');
        }
    }
}
