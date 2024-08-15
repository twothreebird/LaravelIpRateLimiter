<?php

// config for BrenPop/LaravelIpRateLimiter
return [
    'max_attempts' => 20,
    'ttl_minutes' => 1440, // 24 hours
    'whitelist_paths' => [
        // '/',
        // 'quote'
    ],
    'whitelist_ips' => [
        // '000.00.0.00',
    ]
];
