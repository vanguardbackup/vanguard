<?php

declare(strict_types=1);

namespace App\Support\ServerConnection\Exceptions;

use Exception;
use Throwable;

/**
 * ConnectionException
 *
 * This exception is thrown when there are issues with server connections.
 */
final class ConnectionException extends Exception
{
    /**
     * Private constructor to prevent direct instantiation.
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  Throwable|null  $throwable  The previous throwable used for exception chaining
     */
    private function __construct(string $message = '', int $code = 0, ?Throwable $throwable = null)
    {
        parent::__construct($message, $code, $throwable);
    }

    /**
     * Create a new connection exception for authentication failure.
     */
    public static function authenticationFailed(): self
    {
        return new self('Failed to authenticate with the remote server.');
    }

    /**
     * Create a new connection exception for connection failure.
     */
    public static function connectionFailed(): self
    {
        return new self('Failed to establish a connection with the remote server.');
    }

    /**
     * Create a new connection exception for command execution failure.
     *
     * @param  string  $command  The command that failed
     */
    public static function commandFailed(string $command): self
    {
        return new self("Failed to execute command: {$command}");
    }

    /**
     * Create a new connection exception for file transfer failure.
     *
     * @param  string  $operation  The operation that failed (e.g., 'upload', 'download')
     * @param  string  $path  The path of the file
     */
    public static function fileTransferFailed(string $operation, string $path): self
    {
        return new self("Failed to {$operation} file: {$path}");
    }

    /**
     * Create a new connection exception with a custom message.
     *
     * @param  string  $message  The custom error message
     */
    public static function withMessage(string $message): self
    {
        return new self($message);
    }
}
