<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

/**
 * Service for generating time-appropriate and holiday greetings.
 *
 * Provides methods to automatically determine the appropriate
 * greeting based on the time of day or a holiday in a given timezone.
 */
class GreetingService
{
    /**
     * Get an automatic greeting based on the current time or holiday.
     */
    public function auto(string $timezone = 'UTC'): string
    {
        $currentDate = Carbon::now($timezone)->format('Y-m-d');

        // Check if today is a holiday
        $holidayGreeting = $this->holidayGreeting($currentDate);
        if ($holidayGreeting !== null) {
            return $holidayGreeting;
        }

        // Otherwise, return a time-based greeting
        $hour = Carbon::now($timezone)->hour;

        if ($hour < 12) {
            return $this->morning();
        }

        if ($hour < 18) {
            return $this->afternoon();
        }

        return $this->evening();
    }

    /**
     * Get the morning greeting.
     */
    private function morning(): string
    {
        return __('Good morning');
    }

    /**
     * Get the afternoon greeting.
     */
    private function afternoon(): string
    {
        return __('Good afternoon');
    }

    /**
     * Get the evening greeting.
     */
    private function evening(): string
    {
        return __('Good evening');
    }

    /**
     * Get the holiday greeting for a specific date.
     */
    private function holidayGreeting(string $date): ?string
    {
        $currentYear = Carbon::now()->year;
        $holidays = [
            "{$currentYear}-01-01" => (string) __('Happy New Year'),
            "{$currentYear}-04-01" => (string) __("Happy April Fools' Day"),
            "{$currentYear}-12-25" => (string) __('Merry Christmas'),
            "{$currentYear}-12-26" => (string) __('Happy Boxing Day'),
            "{$currentYear}-10-31" => (string) __('Happy Halloween'),
        ];

        return $holidays[$date] ?? null;
    }
}
