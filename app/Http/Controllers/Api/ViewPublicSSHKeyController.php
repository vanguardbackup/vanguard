<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ServerConnection\ServerConnectionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

/**
 * Handles requests to view and format the public SSH key.
 *
 * This controller is responsible for retrieving the default public SSH key
 * and formatting it for easy addition to a remote server's authorized_keys file.
 */
class ViewPublicSSHKeyController extends Controller
{
    /**
     * Handle the incoming request to view and format the public SSH key.
     */
    public function __invoke(): JsonResponse
    {
        if (! $this->sshKeysExist()) {
            return Response::json(['message' => 'Please generate SSH keys first.'], 404);
        }

        return Response::json(['public_key' => ServerConnectionManager::getDefaultPublicKey()]);
    }

    /**
     * Check if SSH keys exist.
     */
    protected function sshKeysExist(): bool
    {
        return ssh_keys_exist();
    }
}
