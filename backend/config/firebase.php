<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | This file contains the Firebase credentials for your project.
    | You can find these in your Firebase Console under Project Settings > Service Accounts.
    |
    */

    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS_FILE', storage_path('firebase-credentials.json')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | The Firebase project ID from your Firebase Console.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Database URL
    |--------------------------------------------------------------------------
    |
    | The Firebase Realtime Database URL (optional).
    |
    */

    'database_url' => env('FIREBASE_DATABASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Storage Bucket
    |--------------------------------------------------------------------------
    |
    | The Firebase Storage bucket name (optional).
    |
    */

    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Auth Emulator Host
    |--------------------------------------------------------------------------
    |
    | The Firebase Auth emulator host for local development (optional).
    |
    */

    'auth_emulator_host' => env('FIREBASE_AUTH_EMULATOR_HOST'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Firestore Emulator Host
    |--------------------------------------------------------------------------
    |
    | The Firebase Firestore emulator host for local development (optional).
    |
    */

    'firestore_emulator_host' => env('FIREBASE_FIRESTORE_EMULATOR_HOST'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Database Emulator Host
    |--------------------------------------------------------------------------
    |
    | The Firebase Database emulator host for local development (optional).
    |
    */

    'database_emulator_host' => env('FIREBASE_DATABASE_EMULATOR_HOST'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Storage Emulator Host
    |--------------------------------------------------------------------------
    |
    | The Firebase Storage emulator host for local development (optional).
    |
    */

    'storage_emulator_host' => env('FIREBASE_STORAGE_EMULATOR_HOST'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable Firebase logging.
    |
    */

    'logging' => [
        'http_logging' => env('FIREBASE_HTTP_LOGGING', false),
        'http_logging_options' => [
            'log_successful_requests' => env('FIREBASE_LOG_SUCCESSFUL_REQUESTS', false),
            'log_failed_requests' => env('FIREBASE_LOG_FAILED_REQUESTS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cache
    |--------------------------------------------------------------------------
    |
    | Firebase cache configuration.
    |
             */

            'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),

            /*
    |--------------------------------------------------------------------------
    | Firebase HTTP Client
    |--------------------------------------------------------------------------
    |
    | Firebase HTTP client configuration.
    |
             */

            'http_client_options' => [
        'timeout' => env('FIREBASE_HTTP_TIMEOUT', 30),
        'proxy' => env('FIREBASE_HTTP_PROXY'),
        'verify' => env('FIREBASE_HTTP_VERIFY', true),
    ],
];
