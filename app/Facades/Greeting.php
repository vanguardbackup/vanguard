<?php

declare(strict_types=1);

namespace App\Facades;

use Override;
use App\Services\GreetingService;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the GreetingService.
 *
 * Provides a convenient static interface to the GreetingService functionality.
 */
class Greeting extends Facade
{
    /**
     * Get the registered name of the component.
     */
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return GreetingService::class;
    }
}
