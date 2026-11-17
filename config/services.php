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

    'prospeccion' => [
        // Microservicio Python (FastAPI + Playwright). Contrato: POST {url}/prospectar
        'url' => env('PROSPECCION_IA_URL'),
        'token' => env('PROSPECCION_IA_TOKEN'),
        'timeout' => (int) env('PROSPECCION_IA_TIMEOUT', 60),
    ],

    // GEST-94: Niubiz (VisaNet Perú) para cobrar la suscripción con tarjeta y renovarla sola.
    // Sin estas variables el pago con tarjeta aparece como "no disponible" y se usan Yape, Plin o transferencia.
    'niubiz' => [
        'merchant_id' => env('NIUBIZ_MERCHANT_ID'),
        'usuario' => env('NIUBIZ_USUARIO'),
        'clave' => env('NIUBIZ_CLAVE'),
        'produccion' => (bool) env('NIUBIZ_PRODUCCION', false),
    ],

    // GEST-92: ingreso con Google (console.cloud.google.com → Credenciales → ID de cliente OAuth)
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
