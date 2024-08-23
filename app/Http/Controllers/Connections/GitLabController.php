<?php

declare(strict_types=1);

namespace App\Http\Controllers\Connections;

use App\Models\UserConnection;
use Exception;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Two\InvalidStateException;

class GitLabController extends ConnectionsController
{
    /**
     * Redirect the user to GitLab's authentication page.
     */
    public function redirect(): RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        return $this->redirectToProvider(UserConnection::PROVIDER_GITLAB);
    }

    /**
     * Handle the callback from GitLab.
     */
    public function callback(): RedirectResponse
    {
        try {
            return $this->handleProviderCallback(UserConnection::PROVIDER_GITLAB);
        } catch (InvalidStateException) {
            return $this->handleInvalidState();
        } catch (Exception $e) {
            return $this->handleGenericError($e);
        }
    }

    /**
     * Handle invalid state exception, which could occur if the user cancels the process.
     */
    protected function handleInvalidState(): RedirectResponse
    {
        return redirect()->route('login')
            ->with('loginError', 'GitLab connection was cancelled or invalid. Please try again if you want to connect your account.');
    }

    /**
     * Handle generic errors during the GitLab connection process.
     */
    protected function handleGenericError(Exception $exception): RedirectResponse
    {
        // Log the error for debugging
        logger()->error('GitLab connection error', ['error' => $exception->getMessage()]);

        return redirect()->route('login')
            ->with('loginError', 'An error occurred while connecting to GitLab. Please try again later.');
    }
}
