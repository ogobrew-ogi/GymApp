<?php

declare(strict_types=1);

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => null,
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Deliberately absent. Per the Architecture Specification (§Password
    | Reset), there is no self-service "forgot password" feature and no
    | email infrastructure — passwords are only ever set by an admin
    | (User Management) or changed by the user themselves while logged in
    | (Change Password). The password_reset_tokens table was never
    | created (see the users migration) and no Password broker is used.
    |
    */

    'password_timeout' => 10800,

];
