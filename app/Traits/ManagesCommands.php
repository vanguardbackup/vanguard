<?php

declare(strict_types=1);

namespace App\Support\ServerConnection\Concerns;

use App\Support\ServerConnection\Exceptions\ConnectionException;

trait ManagesCommands
{
    /**
     * Run a command on the server.
     *
     * @param  string  $command  The command to run
     * @return string The command output
     *
     * @throws ConnectionException If the connection is not established
     */
    public function run(string $command): string
    {
        if (! $this->isConnected()) {
            throw new ConnectionException('No active connection. Please connect first.');
        }

        $output = $this->connection->exec($command);

        if ($output === false) {
            throw new ConnectionException('Failed to execute command: ' . $command);
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
        if (! $this->isConnected()) {
            throw new ConnectionException('No active connection. Please connect first.');
        }

        $this->connection->exec($command, function ($stream) use ($callback): void {
            $buffer = '';
            while ($buffer = fgets($stream)) {
                $callback($buffer);
            }
        });
    }
}
