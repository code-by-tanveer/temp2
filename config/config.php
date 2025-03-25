<?php
return [
    'db' => [
        'dsn' => 'sqlite:' . __DIR__ . '/../data/database.sqlite',
        'username' => null,
        'password' => null,
    ],
    'jwt' => [
        'secret' => 'super-secret-key',
        'algo' => 'HS256'
    ],
    'rate_limit' => [
        'limit' => 1000,
        'window' => 60,  // per minute
    ],
    'redis' => [
        'host' => 'redis',
        'port' => 6379,
        // 'password' => 'redis_password'
    ],
    'app' => [ // Add or modify 'app' array
        'message_edit_time_limit' => 300, // 5 minutes in seconds
    ]

];
