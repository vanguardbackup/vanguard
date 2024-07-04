<?php

declare(strict_types=1);

use App\Facades\Greeting;
use Carbon\Carbon;

it('returns Good morning', function (): void {
    Carbon::setTestNow(Carbon::createFromTime(8, 0, 0, 'UTC'));

    expect(Greeting::auto('UTC'))->toBe('Good morning');

    Carbon::setTestNow();
});

it('returns Good afternoon', function (): void {
    Carbon::setTestNow(Carbon::createFromTime(14, 0, 0, 'UTC'));

    expect(Greeting::auto('UTC'))->toBe('Good afternoon');

    Carbon::setTestNow();
});

it('returns Good evening', function (): void {
    Carbon::setTestNow(Carbon::createFromTime(20, 0, 0, 'UTC'));

    expect(Greeting::auto('UTC'))->toBe('Good evening');

    Carbon::setTestNow();
});

it('handles different timezones correctly', function (): void {
    Carbon::setTestNow(Carbon::createFromTime(8, 0, 0, 'UTC'));

    expect(Greeting::auto('Europe/Berlin'))->toBe('Good morning')
        ->and(Greeting::auto('Asia/Shanghai'))->toBe('Good afternoon')
        ->and(Greeting::auto('America/New_York'))->toBe('Good morning');

    Carbon::setTestNow();
});
