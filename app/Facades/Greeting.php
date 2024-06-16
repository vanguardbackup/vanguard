<?php

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
