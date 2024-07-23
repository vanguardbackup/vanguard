<?php

declare(strict_types=1);

namespace App\Support\ServerConnection\Concerns;

trait ManagesConnections
{
    protected static ?string $defaultPrivateKey = null;
    protected static ?string $defaultPassphrase = null;

    /**
     * Set the default private key path.
     */
    public static function setDefaultPrivateKey(string $path): void
    {
        self::$defaultPrivateKey = $path;
    }

    /**
     * Set the default passphrase.
     */
    public static function setDefaultPassphrase(string $passphrase): void
    {
        self::$defaultPassphrase = $passphrase;
    }

    /**
     * Set connection details manually.
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
     */
    public function withPassword(string $password): self
    {
        $this->password = $password;
        $this->privateKey = null;

        return $this;
    }

    /**
     * Set the authentication method to private key.
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
     */
    protected function getDefaultPrivateKey(): ?string
    {
        return self::$defaultPrivateKey ?? storage_path('app/ssh/id_rsa');
    }

    /**
     * Get the default passphrase.
     */
    protected function getDefaultPassphrase(): ?string
    {
        return self::$defaultPassphrase;
    }
}
