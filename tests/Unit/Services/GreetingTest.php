<?php

use App\Facades\Greeting;
use Carbon\Carbon;

it('returns Good morning', function () {
    Carbon::setTestNow(Carbon::createFromTime(8, 0, 0, 'UTC'));

    expect(Greeting::auto('UTC'))->toBe('Good morning');

    Carbon::setTestNow();
});

it('returns Good afternoon', function () {
    Carbon::setTestNow(Carbon::createFromTime(14, 0, 0, 'UTC'));

    expect(Greeting::auto('UTC'))->toBe('Good afternoon');

    Carbon::setTestNow();
});

it('returns Good evening', function () {
    Carbon::setTestNow(Carbon::createFromTime(20, 0, 0, 'UTC'));

    expect(Greeting::auto('UTC'))->toBe('Good evening');

    Carbon::setTestNow();
});

it('handles different timezones correctly', function () {
    Carbon::setTestNow(Carbon::createFromTime(8, 0, 0, 'UTC'));

    expect(Greeting::auto('Europe/Berlin'))->toBe('Good morning')
        ->and(Greeting::auto('Asia/Shanghai'))->toBe('Good afternoon')
        ->and(Greeting::auto('America/New_York'))->toBe('Good morning');

    Carbon::setTestNow();
});
