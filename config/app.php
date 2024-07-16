<?php

return [

    'name' => env('APP_NAME', 'Vanguard'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    'ssh' => [
        'private_key' => storage_path('app/ssh/key'),
        'public_key' => storage_path('app/ssh/key.pub'),
        'passphrase' => env('SSH_PASSPHRASE', ''),
    ],

    // You can use the below array to add additional languages.
    // Make sure the key is a valid ISO language code - https://en.wikipedia.org/wiki/List_of_ISO_639_language_codes
    'available_languages' => [
        'en' => 'English (English)',
        'da' => 'Danish (Dansk)',
    ],
];
