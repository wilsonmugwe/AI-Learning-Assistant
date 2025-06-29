<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

   
   
    'allowed_origins_patterns' => [
        '^https://.*\.netlify\.app$',         // allow Netlify
        '^https://.*\.onrender\.com$',        // allow Render internal use
    ],

     'allowed_methods' => ['*'],





    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // Set to true only if using cookies

];
