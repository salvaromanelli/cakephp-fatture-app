<?php
return [
    'debug' => env('DEBUG', false),

    'Security' => [
        'salt' => env('SECURITY_SALT', 'your-production-salt-change-this'),
    ],

    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'username' => env('DB_USERNAME', 'cakephp_user'),
            'password' => env('DB_PASSWORD', ''),
            'database' => env('DB_DATABASE', 'cakephp_db'),
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'flags' => [],
            'cacheMetadata' => true,
            'log' => false,
            'quoteIdentifiers' => false,
            'url' => env('DATABASE_URL', null),
        ],
    ],

    'EmailTransport' => [
        'default' => [
            'className' => 'Smtp',
            'host' => env('SMTP_HOST', 'smtp.gmail.com'),
            'port' => env('SMTP_PORT', 587),
            'username' => env('SMTP_USERNAME'),
            'password' => env('SMTP_PASSWORD'),
            'tls' => true,
        ],
    ],

    'Email' => [
        'default' => [
            'transport' => 'default',
            'from' => [env('EMAIL_FROM', 'noreply@yourapp.com') => 'CakePHP Fatture App'],
        ],
    ],

    'Cache' => [
        'default' => [
            'className' => 'Cake\Cache\Engine\FileEngine',
            'path' => CACHE,
            'url' => env('CACHE_DEFAULT_URL', null),
        ],
        '_cake_core_' => [
            'className' => 'Cake\Cache\Engine\FileEngine',
            'prefix' => 'myapp_cake_core_',
            'path' => CACHE . 'persistent/',
            'serialize' => true,
            'duration' => '+1 years',
            'url' => env('CACHE_CAKECORE_URL', null),
        ],
    ],

    'Log' => [
        'default' => [
            'className' => 'Cake\Log\Engine\FileLog',
            'path' => LOGS,
            'file' => 'cakephp',
            'levels' => ['notice', 'info', 'debug'],
            'url' => env('LOG_DEFAULT_URL', null),
        ],
        'error' => [
            'className' => 'Cake\Log\Engine\FileLog',
            'path' => LOGS,
            'file' => 'error',
            'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
            'url' => env('LOG_ERROR_URL', null),
        ],
    ],

    'Session' => [
        'defaults' => 'php',
        'cookie' => 'CAKEPHP',
        'cookieTimeout' => 0,
        'timeout' => 240,
        'ini' => [
            'session.cookie_secure' => true,
            'session.cookie_httponly' => true,
            'session.cookie_samesite' => 'Strict',
        ],
    ],
];
