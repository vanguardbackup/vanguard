<?php

declare(strict_types=1);

namespace App\Support\ServerConnection\Concerns;

/**
 * Trait ManagesConnections
 *
 * This trait provides methods for managing SSH connections and authentication.
 */
trait ManagesConnections
{
    /** @var string|null The default private key path */
    protected static ?string $defaultPrivateKey = null;

    /** @var string|null The default passphrase for the private key */
    protected static ?string $defaultPassphrase = null;

    /**
     * Set the default private key path.
     *
     * @param  string  $path  The path to the private key file
     */
    public static function setDefaultPrivateKey(string $path): void
    {
        self::$defaultPrivateKey = $path;
    }

    /**
     * Set the default passphrase.
     *
     * @param  string  $passphrase  The passphrase for the private key
     */
    public static function setDefaultPassphrase(string $passphrase): void
    {
        self::$defaultPassphrase = $passphrase;
    }

    /**
     * Set connection details manually.
     *
     * @param  string  $host  The hostname or IP address
     * @param  int  $port  The port number (default: 22)
     * @param  string  $username  The username (default: 'root')
     */
    public function to(string $host, int $port = 22, string $username = 'root'): self
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;

        return $this;
    }

    /**
     * Set the authentication method to password.
     *
     * @param  string  $password  The password for authentication
     */
    public function withPassword(string $password): self
    {
        $this->password = $password;
        $this->privateKey = null;

        return $this;
    }

    /**
     * Set the authentication method to private key.
     *
     * @param  string  $privateKeyPath  The path to the private key file
     * @param  string|null  $passphrase  The passphrase for the private key (optional)
     */
    public function withPrivateKey(string $privateKeyPath, ?string $passphrase = null): self
    {
        $this->privateKey = $privateKeyPath;
        $this->passphrase = $passphrase ?? $this->getDefaultPassphrase();
        $this->password = null;

        return $this;
    }

    /**
     * Get the default private key path.
     *
     * @return string|null The path to the default private key file
     */
    protected function getDefaultPrivateKey(): ?string
    {
        return self::$defaultPrivateKey ?? storage_path('app/ssh/id_rsa');
    }

    /**
     * Get the default passphrase.
     *
     * @return string|null The default passphrase for the private key
     */
    protected function getDefaultPassphrase(): ?string
    {
        return self::$defaultPassphrase;
    }
}
