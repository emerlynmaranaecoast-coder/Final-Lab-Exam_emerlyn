<?php
/**
 * Central configuration file.
 *
 * This is the ONLY file you need to edit when moving the app to a new host.
 * On InfinityFree, copy the values from  Client Area -> Hosting Accounts ->
 * MySQL Databases (host looks like sqlXXX.infinityfree.com).
 *
 * Every value can also be overridden by an environment variable of the same
 * name (e.g. SIS_DB_HOST) so the app can be containerised later without code
 * changes (12-factor style).
 */

$env = static fn(string $key, $default) => getenv($key) !== false ? getenv($key) : $default;

return [
    'app' => [
        'name'             => 'Student Information System',
        'env'              => $env('SIS_ENV', 'production'), // 'production' hides error details, 'development' shows them
        'timezone'         => 'Asia/Manila',
        'per_page'         => 10,                            // rows per page in list screens
        'session_lifetime' => 1800,                          // seconds of inactivity before auto-logout (30 min)
        'max_login_tries'  => 5,                             // failed logins before temporary lock
        'lock_minutes'     => 15,                            // length of that lock
    ],

    'db' => [
        'driver'  => $env('SIS_DB_DRIVER', 'mysql'),         // 'mysql' (default). 'sqlite' is only used for local tests.
        'host'    => $env('SIS_DB_HOST', 'sqlXXX.infinityfree.com'),
        'port'    => (int) $env('SIS_DB_PORT', 3306),
        'name'    => $env('SIS_DB_NAME', 'if0_XXXXXXXX_sis'),
        'user'    => $env('SIS_DB_USER', 'if0_XXXXXXXX'),
        'pass'    => $env('SIS_DB_PASS', 'your-db-password'),
        'charset' => 'utf8mb4',
        'sqlite_path' => $env('SIS_DB_SQLITE', ''),
    ],
];
