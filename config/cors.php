<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('APP_URL'),
        'http://localhost:5173',
        'http://localhost:4173',
        'http://localhost:8000',
        'https://*.ngrok-free.app',
        'https://*.ngrok.io',
    ],

    'allowed_origins_patterns' => [
        '#^https://.*\.ngrok-free\.app$#',
        '#^https://.*\.ngrok\.io$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
