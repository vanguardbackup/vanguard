<?php

declare(strict_types=1);

namespace App\Http\Controllers\Connections;

use App\Models\UserConnection;
use Exception;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Two\InvalidStateException;

class GitHubController extends ConnectionsController
{
    /**
     * Redirect the user to GitHub's authentication page.
     */
    public function redirect(): RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        return $this->redirectToProvider(UserConnection::PROVIDER_GITHUB);
    }

    /**
     * Handle the callback from GitHub.
     */
    public function callback(): RedirectResponse
    {
        try {
            return $this->handleProviderCallback(UserConnection::PROVIDER_GITHUB);
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
            ->with('info', 'GitHub connection was cancelled or invalid. Please try again if you want to connect your account.');
    }

    /**
     * Handle generic errors during the GitHub connection process.
     */
    protected function handleGenericError(Exception $exception): RedirectResponse
    {
        // Log the error for debugging
        logger()->error('GitHub connection error', ['error' => $exception->getMessage()]);

        return redirect()->route('login')
            ->with('error', 'An error occurred while connecting to GitHub. Please try again later.');
    }
}
