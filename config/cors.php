<?php

return [
    // Include auth/* so /auth/login and other auth endpoints receive CORS headers on preflight
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'auth/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'https://audit-system-laravel-production.up.railway.app,https://audit-system-orpin.vercel.app')),
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'X-XSRF-TOKEN',
        'X-HTTP-Method-Override',
        'ngrok-skip-browser-warning'
    ],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
