<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    | CORS_ALLOWED_ORIGINS env var accepts a comma-separated list of origins.
    | Example: https://yourapp.vercel.app,https://yourapp.netlify.app
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', implode(',', [
            // Railway backend domain itself
            'https://digital-menu-backend-production.up.railway.app',
            // Local development
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://localhost:3000',
            'http://localhost:8000',
            // Add your deployed frontend URL here or set CORS_ALLOWED_ORIGINS in Railway env
        ])))
    )),

    'allowed_origins_patterns' => [
        // Allows any *.railway.app subdomain automatically
        '#^https://.*\.railway\.app$#',
        // Allows any *.vercel.app subdomain automatically
        '#^https://.*\.vercel\.app$#',
        // Allows any *.netlify.app subdomain automatically
        '#^https://.*\.netlify\.app$#',
    ],

    'allowed_headers' => ['Content-Type', 'Accept', 'Authorization', 'X-Requested-With', 'Origin'],

    'exposed_headers' => [],

    'max_age' => 86400, // 24 hours — browsers cache preflight results

    'supports_credentials' => false,

];
