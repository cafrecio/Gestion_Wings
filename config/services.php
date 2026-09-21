<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Avisos operativos por Telegram. El robot y el chat son los mismos que usan
    // los scripts del servidor (scripts/servidor/monitoreo-common.sh): ahi viven
    // en /etc/wings-monitor/alertas.env y aca en el .env de la aplicacion, porque
    // son dos procesos distintos. Mismo token, dos lugares.
    //
    // Sin estas dos variables el canal no manda nada y lo deja dicho en el log:
    // en una maquina de desarrollo eso es lo correcto, no un error.
    "telegram" => [
        "bot_token" => env("TELEGRAM_BOT_TOKEN"),
        "chat_id"   => env("TELEGRAM_CHAT_ID"),
    ],

];