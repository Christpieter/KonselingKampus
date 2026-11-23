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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openrouter' => [
        'key'          => env('OPENROUTER_API_KEY'),
        'base_url'     => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model'        => env('OPENROUTER_MODEL', 'openai/gpt-oss-20b'),
        'chat_enabled' => env('OPENROUTER_CHAT_ENABLED', false),
    ],

    'nvidia_nim' => [
        'key'          => env('NVIDIA_API_KEY'),
        'base_url'     => env('NVIDIA_NIM_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        'model'        => env('NVIDIA_NIM_MODEL', 'openai/gpt-oss-20b'),
        'chat_enabled' => env('NVIDIA_NIM_CHAT_ENABLED', false),
    ],

    'n8n' => [
        'triage_webhook_url' => env('N8N_TRIAGE_WEBHOOK_URL'),
        'counseling_report_webhook_url' => env('N8N_COUNSELING_REPORT_WEBHOOK_URL'),
        'webhook_secret'     => env('N8N_WEBHOOK_SECRET'),
    ],

];
