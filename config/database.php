<?php

return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../storage/database.sqlite',
            'foreign_keys' => true,
            'wal_mode' => true,
        ],
    ],
];
