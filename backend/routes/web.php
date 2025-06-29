<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| These routes respond to web (non-API) requests and are loaded via the
| RouteServiceProvider. All use the "web" middleware group.
*/

// Health check route
Route::get('/', function () {
    return response()->json(['status' => 'OK']);
});

// Manual cache clearing for production
Route::get('/clear-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return response()->json(['status' => 'Cache cleared']);
});

// Check if .env is loading the OpenAI key correctly
Route::get('/debug/env', function () {
    return response()->json([
        'OPENAI_API_KEY' => env('OPENAI_API_KEY')
    ]);
});

// Test an actual OpenAI API call
Route::get('/debug/openai', function () {
    $apiKey = env('OPENAI_API_KEY');

    if (empty($apiKey)) {
        return response()->json(['error' => 'API key missing']);
    }

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'Say hello.'],
        ],
        'max_tokens' => 5,
    ]);

    if ($response->successful()) {
        return response()->json([
            'openai_response' => $response->json('choices.0.message.content')
        ]);
    } else {
        Log::error('OpenAI API error', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        return response()->json([
            'error' => 'OpenAI API failed',
            'status' => $response->status(),
            'body' => $response->body()
        ]);
    }
});

// Catch-all: Must be last
Route::get('/{any}', function () {
    $path = public_path('index.html');
    return File::exists($path) ? response()->file($path) : abort(404);
})->where('any', '.*');
