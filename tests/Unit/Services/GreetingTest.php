<?php

declare(strict_types=1);

use App\Services\GreetingService;
use Carbon\Carbon;

it('returns Good morning', function (): void {
    // Set the date to a non-holiday date
    Carbon::setTestNow(Carbon::create(2024, 1, 2, 8, 0, 0, 'UTC'));
    $greetingService = new GreetingService;

    expect($greetingService->auto('UTC'))->toBe('Good morning');

    Carbon::setTestNow();
});

it('returns Good afternoon', function (): void {
    // Set the date to a non-holiday date
    Carbon::setTestNow(Carbon::create(2024, 1, 2, 14, 0, 0, 'UTC'));
    $greetingService = new GreetingService;

    expect($greetingService->auto('UTC'))->toBe('Good afternoon');

    Carbon::setTestNow();
});

it('returns Good evening', function (): void {
    // Set the date to a non-holiday date
    Carbon::setTestNow(Carbon::create(2024, 1, 2, 20, 0, 0, 'UTC'));
    $greetingService = new GreetingService;

    expect($greetingService->auto('UTC'))->toBe('Good evening');

    Carbon::setTestNow();
});

it('handles different timezones correctly', function (): void {
    // Set the date to a non-holiday date
    Carbon::setTestNow(Carbon::create(2024, 1, 2, 8, 0, 0, 'UTC'));
    $greetingService = new GreetingService;

    expect($greetingService->auto('Europe/Berlin'))->toBe('Good morning')
        ->and($greetingService->auto('Asia/Shanghai'))->toBe('Good afternoon')
        ->and($greetingService->auto('America/New_York'))->toBe('Good morning');

    Carbon::setTestNow();
});

it('returns holiday greeting on Christmas', function (): void {
    $currentYear = Carbon::now()->year;
    Carbon::setTestNow(Carbon::createMidnightDate($currentYear, 12, 25, 'UTC'));
    $greetingService = new GreetingService;

    expect($greetingService->auto('UTC'))->toBe('Merry Christmas');

    Carbon::setTestNow();
});

it('returns holiday greeting on New Year', function (): void {
    $currentYear = Carbon::now()->year;
    Carbon::setTestNow(Carbon::createMidnightDate($currentYear, 1, 1, 'UTC'));
    $greetingService = new GreetingService;

    expect($greetingService->auto('UTC'))->toBe('Happy New Year');

    Carbon::setTestNow();
});

it('returns holiday greeting on Halloween', function (): void {
    $currentYear = Carbon::now()->year;
    Carbon::setTestNow(Carbon::createMidnightDate($currentYear, 10, 31, 'UTC'));
    $greetingService = new GreetingService;

    expect($greetingService->auto('UTC'))->toBe('Happy Halloween');

    Carbon::setTestNow();
});

it('returns holiday greeting on Boxing Day', function (): void {
    $currentYear = Carbon::now()->year;
    Carbon::setTestNow(Carbon::createMidnightDate($currentYear, 12, 26, 'UTC'));
    $greetingService = new GreetingService;

    expect($greetingService->auto('UTC'))->toBe('Happy Boxing Day');

    Carbon::setTestNow();
});

it("returns holiday greeting on April Fools' Day", function (): void {
    $currentYear = Carbon::now()->year;
    Carbon::setTestNow(Carbon::createMidnightDate($currentYear, 4, 1, 'UTC'));
    $greetingService = new GreetingService;

    expect($greetingService->auto('UTC'))->toBe("Happy April Fools' Day");

    Carbon::setTestNow();
});
