<?php

use Knuckles\Scribe\Config\AuthIn;
use Knuckles\Scribe\Config\Defaults;
use Knuckles\Scribe\Extracting\Strategies;

use function Knuckles\Scribe\Config\removeStrategies;

return [
    // Заголовок страницы документации.
    'title' => 'Фитнес-клуб: документация API',

    // Краткое описание API: попадает на страницу, в Postman-коллекцию и OpenAPI.
    'description' => 'REST API фитнес-клуба. Роли: client (клиент), trainer (тренер), admin (администратор). Тестовые пользователи из сидера: admin@fitclub.test, sokolov@fitclub.test, petrov@fitclub.test, пароль password.',

    // Текст раздела «Introduction» после описания.
    'intro_text' => <<<'INTRO'
            Все адреса начинаются с `/api/v1`. Запросы и ответы передаются в формате JSON, заголовок `Accept: application/json` обязателен.

            Ошибки возвращаются в едином формате `{"message": "..."}`: 401 — нет или недействителен токен, 403 — недостаточно прав, 404 — запись не найдена, 409 — конфликт бизнес-правил, 422 — ошибка валидации или нарушение правила.

            <aside>Примеры запросов показаны в тёмной области справа. Язык примеров переключается вкладками вверху.</aside>
        INTRO,

    // Базовый адрес, показываемый в документации.
    'base_url' => config('app.url'),

    // Какие маршруты попадают в документацию.
    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/*'],
                'domains' => ['*'],
            ],

            'include' => [
                // 'users.index', 'POST /new', '/auth/*'
            ],

            // Служебные маршруты в документацию не нужны.
            'exclude' => [
                'GET /api/health',
                'GET /api/user',
                'GET /api/boom',
            ],
        ],
    ],

    // "laravel" — документация как Blade-страница по адресу /docs.
    'type' => 'laravel',

    'theme' => 'default',

    'static' => [
        'output_path' => 'public/docs',
    ],

    'laravel' => [
        'add_routes' => true,
        'docs_url' => '/docs',
        'assets_directory' => null,
        'middleware' => [],
    ],

    'external' => [
        'html_attributes' => [],
    ],

    'try_it_out' => [
        // Кнопка «Try it out» для проверки эндпоинтов прямо в браузере.
        'enabled' => true,
        'base_url' => null,
        'use_csrf' => false,
        'csrf_url' => '/sanctum/csrf-cookie',
    ],

    // Аутентификация: токен Sanctum в заголовке Authorization: Bearer.
    'auth' => [
        'enabled' => true,

        // Часть эндпоинтов открыта всем, поэтому по умолчанию false;
        // закрытые отмечаются тегом @authenticated в PHPDoc.
        'default' => false,

        'in' => AuthIn::BEARER->value,
        'name' => 'key',
        'use_value' => env('SCRIBE_AUTH_KEY'),
        'placeholder' => '{YOUR_TOKEN}',
        'extra_info' => 'Токен выдаётся при входе: <code>POST /api/v1/auth/login</code>. Передавайте его в заголовке <code>Authorization: Bearer {токен}</code>.',
    ],

    'example_languages' => [
        'bash',
        'javascript',
    ],

    'postman' => [
        'enabled' => true,

        'overrides' => [
            // 'info.version' => '2.0.0',
        ],
    ],

    'openapi' => [
        'enabled' => true,
        'version' => '3.0.3',

        'overrides' => [
            // 'info.version' => '2.0.0',
        ],

        'generators' => [],
    ],

    'groups' => [
        'default' => 'Endpoints',
        'order' => [],
    ],

    'logo' => false,

    'last_updated' => 'Обновлено: {date:d.m.Y}',

    'examples' => [
        'faker_seed' => 1234,
        'models_source' => ['factoryCreate', 'factoryMake', 'databaseFirst'],
    ],

    'strategies' => [
        'metadata' => [
            ...Defaults::METADATA_STRATEGIES,
        ],
        'headers' => [
            ...Defaults::HEADERS_STRATEGIES,
            Strategies\StaticData::withSettings(data: [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]),
        ],
        'urlParameters' => [
            ...Defaults::URL_PARAMETERS_STRATEGIES,
        ],
        'queryParameters' => [
            ...Defaults::QUERY_PARAMETERS_STRATEGIES,
        ],
        'bodyParameters' => [
            ...Defaults::BODY_PARAMETERS_STRATEGIES,
        ],
        // Реальные вызовы эндпоинтов отключены: примеры ответов
        // берутся из тегов @response в PHPDoc контроллеров.
        'responses' => removeStrategies(
            Defaults::RESPONSES_STRATEGIES,
            [Strategies\Responses\ResponseCalls::class]
        ),
        'responseFields' => [
            ...Defaults::RESPONSE_FIELDS_STRATEGIES,
        ],
    ],

    // Транзакции для вызовов не нужны, так как вызовы отключены.
    'database_connections_to_transact' => [],

    'fractal' => [
        'serializer' => null,
    ],
];