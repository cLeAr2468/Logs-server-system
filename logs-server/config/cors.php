<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174', 
        'http://localhost:5175',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
        'http://127.0.0.1:5175',
        'https://main.client-side-aqd.pages.dev',
        'https://transact-logs-system.pages.dev',
        'https://client-module.pages.dev',
        env('FRONTEND_URL', ''),
        env('CLIENT_URL', ''),
    ],

    'allowed_origins_patterns' => [
        // Allow all Vercel preview deployments
        '/^https:\/\/.*\.vercel\.app$/',
        // Allow all Cloudflare Pages deployments
        '/^https:\/\/.*\.pages\.dev$/',
    ],
    
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];