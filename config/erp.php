<?php

return [

    /*
    |--------------------------------------------------------------------------
    | XERIN GROUP ERP — Platform configuration
    |--------------------------------------------------------------------------
    */

    'organization' => env('ERP_ORGANIZATION', 'Xerin Group Company Limited'),

    'localization' => [
        'timezone' => env('APP_TIMEZONE', 'Africa/Dar_es_Salaam'),
        'locale' => env('APP_LOCALE', 'en'),
        'locales' => ['en', 'sw'],
        'currency' => env('ERP_CURRENCY', 'TZS'),
    ],

    'files' => [
        'max_size_kb' => env('ERP_MAX_UPLOAD_KB', 10240),
        'allowed_mimes' => [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
            'application/pdf', 'text/plain', 'text/csv',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],

    'api' => [
        'version' => 'v1',
        'rate_limit' => env('API_RATE_LIMIT', '60,1'), // requests,minutes
    ],

];
