<?php

use Laravel\Pulse\Http\Middleware\Authorize;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders;

return [

    'domain' => env('PULSE_DOMAIN'),

    'path' => env('PULSE_PATH', 'pulse'),

    'enabled' => env('PULSE_ENABLED', true),

    'storage' => [
        'driver' => env('PULSE_STORAGE_DRIVER', 'database'),

        'database' => [
            'connection' => env('PULSE_DB_CONNECTION', null),
            'chunk' => 1000,
        ],
    ],

    'ingest' => [
        'driver' => env('PULSE_INGEST_DRIVER', 'storage'),

        'buffer' => env('PULSE_INGEST_BUFFER', 5_000),

        'trim' => [
            'lottery' => [1, 1_000],
            'keep' => '7 days',
        ],

        'redis' => [
            'connection' => env('PULSE_REDIS_CONNECTION'),
            'chunk' => 1000,
        ],
    ],

    'cache' => env('PULSE_CACHE_DRIVER'),

    'middleware' => [
        'web',
        Authorize::class,
    ],

    'recorders' => [
        Recorders\CacheInteractions::class => [
            'enabled' => env('PULSE_CACHE_INTERACTIONS_ENABLED', true),
            'sample_rate' => env('PULSE_CACHE_INTERACTIONS_SAMPLE_RATE', 1),
            'ignore' => [
                ...Pulse::defaultVendorCacheKeys(),
            ],
            'groups' => [
                '/^job-exceptions:.*/' => 'job-exceptions:*',
                // '/:\d+/' => ':*',
            ],
        ],

        Recorders\Exceptions::class => [
            'enabled' => env('PULSE_EXCEPTIONS_ENABLED', true),
            'sample_rate' => env('PULSE_EXCEPTIONS_SAMPLE_RATE', 1),
            'location' => env('PULSE_EXCEPTIONS_LOCATION', true),
            'ignore' => [
                // '/^Package\\\\Exceptions\\\\/',
            ],
        ],

        Recorders\Queues::class => [
            'enabled' => env('PULSE_QUEUES_ENABLED', true),
            'sample_rate' => env('PULSE_QUEUES_SAMPLE_RATE', 1),
            'ignore' => [
                // '/^Package\\\\Jobs\\\\/',
            ],
        ],

        Recorders\Servers::class => [
            'server_name' => env('PULSE_SERVER_NAME', gethostname()),
            'directories' => explode(':', env('PULSE_SERVER_DIRECTORIES', '/')),
        ],

        Recorders\SlowJobs::class => [
            'enabled' => env('PULSE_SLOW_JOBS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_JOBS_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_JOBS_THRESHOLD', 1000),
            'ignore' => [
                // '/^Package\\\\Jobs\\\\/',
            ],
        ],

        Recorders\SlowOutgoingRequests::class => [
            'enabled' => env('PULSE_SLOW_OUTGOING_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_OUTGOING_REQUESTS_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_OUTGOING_REQUESTS_THRESHOLD', 1000),
            'ignore' => [
                // '#^http://127\.0\.0\.1:13714#', // Inertia SSR...
            ],
            'groups' => [
                // '#^https://api\.github\.com/repos/.*$#' => 'api.github.com/repos/*',
                // '#^https?://([^/]*).*$#' => '\1',
                // '#/\d+#' => '/*',
            ],
        ],

        Recorders\SlowQueries::class => [
            'enabled' => env('PULSE_SLOW_QUERIES_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_QUERIES_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_QUERIES_THRESHOLD', 1000),
            'location' => env('PULSE_SLOW_QUERIES_LOCATION', true),
            'max_query_length' => env('PULSE_SLOW_QUERIES_MAX_QUERY_LENGTH', null),
            'ignore' => [
                '/(["`])pulse_[\w]+?\1/', // Pulse tables...
                '/(["`])telescope_[\w]+?\1/', // Telescope tables...
            ],
        ],

        Recorders\SlowRequests::class => [
            'enabled' => env('PULSE_SLOW_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_REQUESTS_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_REQUESTS_THRESHOLD', 1000),
            'ignore' => [
                '#^/' . env('PULSE_PATH', 'pulse') . '$#', // Pulse dashboard...
                '#^/telescope#', // Telescope dashboard...
            ],
        ],

        Recorders\UserJobs::class => [
            'enabled' => env('PULSE_USER_JOBS_ENABLED', true),
            'sample_rate' => env('PULSE_USER_JOBS_SAMPLE_RATE', 1),
            'ignore' => [
                // '/^Package\\\\Jobs\\\\/',
            ],
        ],

        Recorders\UserRequests::class => [
            'enabled' => env('PULSE_USER_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_USER_REQUESTS_SAMPLE_RATE', 1),
            'ignore' => [
                '#^/' . env('PULSE_PATH', 'pulse') . '$#', // Pulse dashboard...
                '#^/telescope#', // Telescope dashboard...
            ],
        ],
    ],
];
