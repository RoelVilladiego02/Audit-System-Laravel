<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,https://dc630ea5ff0b.ngrok-free.app,https://audit-system-orpin.vercel.app')),
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
