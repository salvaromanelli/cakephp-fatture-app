<?php
return [
    'debug' => false,
    'App' => [
        'namespace' => 'App',
        'encoding' => 'UTF-8',
        'defaultLocale' => 'en_US',
        'defaultTimezone' => 'UTC',
        'paths' => [
            'plugins' => [ROOT . DS . 'plugins' . DS],
            'templates' => [APP . 'templates' . DS],
            'locales' => [RESOURCES . 'locales' . DS],
        ],
    ],
    'Security' => [
        'salt' => '4cae913d40f8b18c0a79fa6e4365ef954b7efd93edda537c8742634369e18a6e',
    ],
    'Asset' => [
        'cacheTime' => '+1 year',
    ],
    'Error' => [
        'errorLevel' => E_ALL & ~E_USER_DEPRECATED,
        'exceptionRenderer' => 'Cake\Error\ExceptionRenderer',
        'skipLog' => [],
        'log' => true,
        'trace' => true,
    ],
];
