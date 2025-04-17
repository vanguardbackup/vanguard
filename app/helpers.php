<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Check if SSH keys exist.
 *
 * This function checks for the existence of both private and public SSH key files.
 *
 * @return bool True if both private and public SSH keys exist, false otherwise.
 */
function ssh_keys_exist(): bool
{
    return file_exists(config('app.ssh.private_key'))
        && file_exists(config('app.ssh.public_key'));
}

/**
 * Get the contents of the SSH public key.
 *
 * @deprecated Please use the ServerConnectionManager static implementation.
 *
 * @return string The contents of the SSH public key.
 *
 * @throws RuntimeException If the public key file cannot be read.
 */
function get_ssh_public_key(): string
{
    $publicKeyPath = config('app.ssh.public_key');
    $publicKey = @file_get_contents($publicKeyPath);

    if ($publicKey === false) {
        throw new RuntimeException('Unable to read SSH public key from: ' . $publicKeyPath);
    }

    return $publicKey;
}

/**
 * Get the contents of the SSH private key.
 *
 * @deprecated Please use the ServerConnectionManager static implementation.
 *
 * @return string The contents of the SSH private key.
 *
 * @throws RuntimeException If the private key file cannot be read.
 */
function get_ssh_private_key(): string
{
    $privateKeyPath = config('app.ssh.private_key');
    $privateKey = @file_get_contents($privateKeyPath);

    if ($privateKey === false) {
        throw new RuntimeException('Unable to read SSH private key from: ' . $privateKeyPath);
    }

    return $privateKey;
}

/**
 * Format timezones into a user-friendly format.
 *
 * This function creates an array of formatted timezone strings, including GMT offset,
 * city name, and region.
 *
 * @return array<string, string> Formatted timezones with keys as timezone identifiers and values as formatted strings.
 *
 * @throws Exception If there's an error creating DateTime objects.
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

/**
 * Obtain the Vanguard version.
 *
 * This function reads the version from a file and caches it for a day.
 * If the version file doesn't exist, it returns 'Unknown'.
 *
 * @return string The Vanguard version or 'Unknown' if the version file is not found.
 */
function obtain_vanguard_version(): string
{
    $versionFile = base_path('VERSION');

    return Cache::remember('vanguard_version', now()->addDay(), static function () use ($versionFile): string {
        if (! File::exists($versionFile)) {
            return 'Unknown';
        }

        return trim(File::get($versionFile));
    });
}

/**
 * Determine if the Year in Review feature is active.
 *
 * The Year in Review feature is considered active if:
 * 1. The `ENABLE_YEAR_IN_REVIEW_SYSTEM` setting in the .env file is set to true.
 * 2. The current date falls within the configured start and end dates (`starts_at` and `ends_at`).
 *
 * If these conditions are met, the feature will be visible in the Account Settings and its route will be active.
 *
 * @return bool True if the feature is active, otherwise false.
 */
function year_in_review_active(): bool
{
    $isEnabled = config('app.year_in_review.enabled');

    if (! $isEnabled) {
        return false;
    }

    $currentYear = now()->year;

    // Parse the static MM-DD dates into full dates with dynamic year.
    $startDateString = $currentYear . '-' . config('app.year_in_review.starts_at');
    $endDateString = ($currentYear + 1) . '-' . config('app.year_in_review.ends_at');

    try {
        $startDate = Carbon::createFromFormat('Y-m-d', $startDateString)?->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $endDateString)?->endOfDay();
    } catch (Exception $e) {
        Log::error('[YEAR IN REVIEW SYSTEM] There was a problem obtaining the start date and end date: ' . $e->getMessage());

        return false; // Ensure invalid configuration doesn't cause errors
    }

    return now()->between((string) $startDate, (string) $endDate);
}

function purge_user_sessions(User $user): void
{
    $sessionDriver = Config::get('session.driver');

    match ($sessionDriver) {
        'database' => clearDatabaseSessions($user),
        'redis' => clearRedisSessions($user),
        'file' => clearFileSessions($user),
        default => Log::error('Could not purge sessions for ' . $sessionDriver),
    };
}

function clearDatabaseSessions(User $user): void
{
    DB::table(Config::get('session.table', 'sessions'))
        ->where('user_id', $user->getAttribute('id'))
        ->delete();
}

function clearRedisSessions(User $user): void
{
    $prefix = Config::get('session.prefix', '');
    $pattern = "{$prefix}:*";

    $connection = Redis::connection(Config::get('session.connection'));
    $keys = $connection->keys($pattern);

    if (is_array($keys)) {
        foreach ($keys as $key) {
            $session = $connection->get($key);
            if (is_string($session) && str_contains($session, "\"user_id\";i:{$user->getAttribute('id')};")) {
                $connection->del($key);
            }
        }
    }
}

function clearFileSessions(User $user): void
{
    $directory = Config::get('session.files');
    $pattern = "{$directory}/sess_*";

    $files = glob($pattern);
    if (is_array($files)) {
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (is_string($content) && str_contains($content, "\"user_id\";i:{$user->getAttribute('id')};")) {
                unlink($file);
            }
        }
    }
}
