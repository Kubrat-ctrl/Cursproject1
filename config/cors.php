<?php

return [

    // CORS действует только для маршрутов API
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Адреса клиентов задаются в .env через запятую. Значение «*» не используется.
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type', 'Authorization', 'X-Requested-With'],

    'exposed_headers' => [],

    // Браузер может кешировать ответ на preflight 10 минут
    'max_age' => 600,

    // Авторизация по Bearer-токену, cookie не нужны
    'supports_credentials' => false,

];
