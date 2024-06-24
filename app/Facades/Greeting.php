<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\GreetingService;
use Illuminate\Support\Facades\Facade;

class Greeting extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GreetingService::class;
    }
}
