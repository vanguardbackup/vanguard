<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

function ssh_keys_exist(): bool
{
    return file_exists(config('app.ssh.private_key')) && file_exists(config('app.ssh.public_key'));
}

function get_ssh_public_key(): string
{
    return file_get_contents(config('app.ssh.public_key'));
}

function get_ssh_private_key(): string
{
    return file_get_contents(config('app.ssh.private_key'));
}

/**
 * Format timezones into a user-friendly format.
 *
 * @return array<string, string> Formatted timezones with keys as timezone identifiers and values as formatted strings.
 *
 * @throws Exception
 */
function formatTimezones(): array
{
    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
    $formattedTimezones = [];

    foreach ($timezones as $timezone) {
        $dateTime = new DateTime('now', new DateTimeZone($timezone));
        $region = explode('/', $timezone)[0];
        $city = explode('/', $timezone)[1] ?? '';
        $city = str_replace('_', ' ', $city);
        $formattedTimezones[$timezone] = '(GMT ' . $dateTime->format('P') . ') ' . $city . ' (' . $region . ')';
    }

    return $formattedTimezones;
}

function obtain_vanguard_version(): string
{
    $versionFile = base_path('VERSION');

    return Cache::remember('vanguard_version', now()->addDay(), static function () use ($versionFile) {
        if (! File::exists($versionFile)) {
            return 'Unknown';
        }

        return trim(File::get($versionFile));
    });
}
