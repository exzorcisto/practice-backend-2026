<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => ['title' => 'Survey API Docs'],
            'routes' => ['api' => 'api/documentation'], // Адрес в браузере
            'paths' => [
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),
                'docs_json' => 'api-docs.json',
                'docs' => storage_path('api-docs'), // Где лежит файл
                'annotations' => [base_path('app')],
            ],
        ],
    ],
    'defaults' => [
        'routes' => ['docs' => 'docs'],
        'paths' => [
            'docs' => storage_path('api-docs'),
            'views' => base_path('resources/views/vendor/l5-swagger'),
            'base' => env('L5_SWAGGER_BASE_PATH', null),
        ],
        'generate_always' => true, // Чтобы он подхватывал изменения JSON сразу
        'swagger_watch_iterations' => 1,
        'always_store_generated_settings' => true,
    ],
];
