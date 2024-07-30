<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

/**
 * Service for generating time-appropriate greetings.
 *
 * Provides methods to automatically determine the appropriate
 * greeting based on the time of day in a given timezone.
 */
class GreetingService
{
    /**
     * Get an automatic greeting based on the current time.
     */
    public function auto(string $timezone = 'UTC'): string
    {
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
}
