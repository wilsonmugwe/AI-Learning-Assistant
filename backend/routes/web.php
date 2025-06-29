<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| This file defines routes that respond to web requests.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "web" middleware group.
*/

// Route to test if the .env key is being read correctly
Route::get('/test-env', function () {
    return response()->json([
        'openai_key' => env('OPENAI_API_KEY') // Pulls OpenAI key from .env
    ]);
});

// Route to test making an actual request to OpenAI API
Route::get('/test-openai', function () {
    $apiKey = env('OPENAI_API_KEY'); // Load OpenAI API key from .env

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
            'result' => $response->json('choices.0.message.content')
        ]);
    } else {
        Log::error('OpenAI API error', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        return response()->json([
            'error' => 'OpenAI API error',
            'status' => $response->status()
        ]);
    }
});

// Catch-all route to serve Vue frontend for all unknown routes
Route::get('/{any}', function () {
    $path = public_path('index.html');
    return File::exists($path) ? response()->file($path) : abort(404);
})->where('any', '.*');
