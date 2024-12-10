<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

it('is active if the date falls within the eligible date and the variable is enabled', function (): void {
    // Set the flag to enabled
    Config::set('app.year_in_review.enabled', true);

    // Mock the start and end dates
    Config::set('app.year_in_review.starts_at', '12-01'); // December 1
    Config::set('app.year_in_review.ends_at', '01-01');   // January 1

    // Set a date within the valid range
    Carbon::setTestNow(Carbon::create(now()->year, 12, 15)); // December 15 of the current year

    // Assert that the feature is active
    expect(year_in_review_active())->toBeTrue();

    // Clean up the mocked date
    Carbon::setTestNow();
});

it('is not active if the feature flag is disabled', function (): void {
    // Disable the flag
    Config::set('app.year_in_review.enabled', false);

    // Set the date to within the valid range
    Carbon::setTestNow(Carbon::create(now()->year, 12, 15)); // December 15 of the current year

    // Assert that the feature is not active
    expect(year_in_review_active())->toBeFalse();

    // Clean up the mocked date
    Carbon::setTestNow();
});

it('is not active if the current date is before the start date', function (): void {
    // Enable the flag
    Config::set('app.year_in_review.enabled', true);

    // Mock the start and end dates
    Config::set('app.year_in_review.starts_at', '12-01'); // December 1
    Config::set('app.year_in_review.ends_at', '01-01');   // January 1

    // Set a date before the start date
    Carbon::setTestNow(Carbon::create(now()->year, 11, 30)); // November 30 of the current year

    // Assert that the feature is not active
    expect(year_in_review_active())->toBeFalse();

    // Clean up the mocked date
    Carbon::setTestNow();
});

it('is not active if the current date is after the end date', function (): void {
    // Enable the flag
    Config::set('app.year_in_review.enabled', true);

    // Mock the start and end dates
    Config::set('app.year_in_review.starts_at', '12-01'); // December 1
    Config::set('app.year_in_review.ends_at', '01-01');   // January 1

    // Set a date after the end date
    Carbon::setTestNow(Carbon::create(now()->year + 1, 1, 2)); // January 2 of the next year

    // Assert that the feature is not active
    expect(year_in_review_active())->toBeFalse();

    // Clean up the mocked date
    Carbon::setTestNow();
});
