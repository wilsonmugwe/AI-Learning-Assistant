<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    // Paths where CORS should apply (API routes and Sanctum if used)
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    // Allow specific frontend origins
    'allowed_origins' => [
        'http://localhost:5173',
        'https://steady-gelato-f939f6.netlify.app',
    ],

    // Additional regex-based rules for dynamic subdomains (optional fallback)
    'allowed_origins_patterns' => [
        '^https://.*\.netlify\.app$',
        '^https://.*\.onrender\.com$',
    ],

    // Allow all common methods (GET, POST, PUT, DELETE, etc.)
    'allowed_methods' => ['*'],

    // Allow any headers (e.g., Content-Type, Authorization)
    'allowed_headers' => ['*'],

    // No custom exposed headers
    'exposed_headers' => [],

    // How long CORS preflight response should be cached
    'max_age' => 0,

    // Whether to send cookies/authorization headers (leave false unless needed)
    'supports_credentials' => false,
];
