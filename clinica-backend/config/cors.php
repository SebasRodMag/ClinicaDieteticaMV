<?php

return [
    'paths' => ['api/*', 'login', 'register', 'logout'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '')))),
    //En secrets/clinica.env permitimos las rutas, tanto para desarrollo como producción
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
