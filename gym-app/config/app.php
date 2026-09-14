<?php

declare(strict_types=1);

return [

    'name' => env('APP_NAME', 'Gym Training'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | This MUST stay UTC. It governs how PHP/Carbon interpret "naive"
    | datetime strings coming out of the database — and per the accepted
    | engineering recommendation (Domain Model Spec §11.1), every stored
    | datetime is UTC. Setting this to Europe/Sofia would make Carbon
    | mis-parse those UTC values as if they were already local time,
    | double-shifting every displayed time.
    |
    | The gym's single display timezone lives in `display_timezone`
    | below and is applied explicitly at the UI boundary (see
    | App\Support\LocalTime) — never implicitly via this setting.
    |
    */

    'timezone' => 'UTC',

    'display_timezone' => 'Europe/Sofia',

    /*
    |--------------------------------------------------------------------------
    | Locale
    |--------------------------------------------------------------------------
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', ''))),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
