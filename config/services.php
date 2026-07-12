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

    'fcm' => [
        'project_id' => env('FIREBASE_PROJECT_ID', 'tabaos-aca40'),
        'credentials' => value(function (): ?string {
            $path = env('FIREBASE_CREDENTIALS');
            if (! is_string($path) || $path === '') {
                return null;
            }

            if ($path[0] === DIRECTORY_SEPARATOR) {
                return $path;
            }

            return base_path($path);
        }),
        // Opsional — hanya jika pakai Firebase Realtime Database (FCM push tidak wajib).
        'database_url' => env('FIREBASE_DATABASE_URL'),
    ],

];
