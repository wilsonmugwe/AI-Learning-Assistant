<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$apiKey = getenv('OPENAI_API_KEY');

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json',
])->post('https://api.openai.com/v1/chat/completions', [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'Say hello.'],
        ['role' => 'user', 'content' => 'Hello OpenAI!'],
    ],
    'max_tokens' => 10,
]);

var_dump($response->json());
