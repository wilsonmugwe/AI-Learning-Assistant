<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| This file defines routes that respond to web requests.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "web" middleware group.
*/

// Root route — returns the default welcome view
Route::get('/', function () {
    return view('welcome');
});

// Route to test if the .env key is being read correctly
Route::get('/test-env', function () {
    return response()->json([
        'openai_key' => env('OPENAI_API_KEY') // Pulls OpenAI key from .env
    ]);
});

// Route to test making an actual request to OpenAI API
Route::get('/test-openai', function () {
    $apiKey = env('OPENAI_API_KEY'); // Load OpenAI API key from .env

    // If key is missing, return an error response
    if (empty($apiKey)) {
        return response()->json(['error' => 'API key missing']);
    }

    // Make a POST request to OpenAI’s Chat Completion endpoint
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'Say hello.'], // Simple prompt to test if it's working
        ],
        'max_tokens' => 5, // Limit token usage
    ]);

    // If OpenAI responds successfully, return the message content
    if ($response->successful()) {
        return response()->json([
            'result' => $response->json('choices.0.message.content')
        ]);
    } else {
        // If request fails, log error and return failure response
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
