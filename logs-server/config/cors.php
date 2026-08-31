<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174', 
        'http://localhost:5175',
        // Add your production frontend URLs here after deployment
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