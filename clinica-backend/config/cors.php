<?php

return [
    'paths' => ['api/*', 'login', 'register', 'logout'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://app.asrm.dev'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
