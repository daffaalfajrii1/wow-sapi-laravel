<?php

return [
    'ai' => [
        'url' => env('WOWSAPI_AI_URL', env('FASTAPI_URL', 'http://127.0.0.1:8003')),
        'timeout' => (int) env('WOWSAPI_AI_TIMEOUT', 60),
        'paths' => [
            'health' => env('WOWSAPI_AI_HEALTH_PATH', '/health'),
            'weight' => env('WOWSAPI_AI_WEIGHT_PATH', '/predict/weight'),
            'lumpy' => env('WOWSAPI_AI_LUMPY_PATH', '/predict/lumpy'),
            'analyze' => env('WOWSAPI_AI_ANALYZE_PATH', '/predict/all'),
            'bcs' => env('WOWSAPI_AI_BCS_PATH', '/predict/bcs'),
        ],
        'bcs_enabled' => (bool) env('WOWSAPI_AI_BCS_ENABLED', false),
    ],

    'otp' => [
        'ttl_minutes' => 5,
        'max_attempts' => 5,
        'resend_seconds' => 60,
        'resend_max_per_window' => 3,
        'resend_window_minutes' => 10,
    ],

    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'client_email' => env('FCM_CLIENT_EMAIL'),
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    ],
];
