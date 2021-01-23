<?php
return [
    'sql-request' => [
        'driver' => 'daily',
        'path' => storage_path('logs/sql-request.log'),
        'level' => 'debug',
        'days' => 14,
    ],
    'sql-console' => [
        'driver' => 'daily',
        'path' => storage_path('logs/sql-console.log'),
        'level' => 'debug',
        'days' => 14,
    ],
    'sql-slow' => [
        'driver' => 'daily',
        'path' => storage_path('logs/sql-slow.log'),
        'level' => 'debug',
        'days' => 14,
    ]
];
