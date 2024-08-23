<?php

declare(strict_types=1);

namespace App\Http\Controllers\Connections;

use App\Models\UserConnection;
use Exception;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class BitbucketController extends ConnectionsController
{
    /** @var string The provider name for Bitbucket */
    private const PROVIDER_NAME = UserConnection::PROVIDER_BITBUCKET;

    /**
     * Redirect the user to Bitbucket's authentication page.
     */
    public function redirect(): RedirectResponse|SymfonyRedirectResponse
    {
        return $this->redirectToProvider(self::PROVIDER_NAME);
    }

    /**
     * Handle the callback from Bitbucket.
     */
    public function callback(): RedirectResponse
    {
        try {
            return $this->handleProviderCallback(self::PROVIDER_NAME);
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
            ->with('loginError', 'Bitbucket connection was cancelled or invalid. Please try again if you want to connect your account.');
    }

    /**
     * Handle generic errors during the Bitbucket connection process.
     */
    protected function handleGenericError(Exception $exception): RedirectResponse
    {
        logger()->error('Bitbucket connection error', ['error' => $exception->getMessage()]);

        return redirect()->route('login')
            ->with('loginError', 'An error occurred while connecting to Bitbucket. Please try again later.');
    }
}
