<?php

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

function formatTimezones(): array
{
    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
    $formattedTimezones = [];

    foreach ($timezones as $timezone) {
        $dateTime = new DateTime('now', new DateTimeZone($timezone));
        $region = explode('/', $timezone)[0];
        $city = explode('/', $timezone)[1] ?? '';
        $city = str_replace('_', ' ', $city);
        $formattedTimezones[$timezone] = '(GMT '.$dateTime->format('P').') '.$city.' ('.$region.')';
    }

    return $formattedTimezones;
}
