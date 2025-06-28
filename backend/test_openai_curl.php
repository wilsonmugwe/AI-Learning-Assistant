<?php
require 'vendor/autoload.php';

// Load .env variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = getenv('OPENAI_API_KEY');

if (!$apiKey) {
    echo "OpenAI API key not found in environment variables.\n";
    exit(1);
}

$ch = curl_init('https://api.openai.com/v1/chat/completions');

$data = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Say hello!'],
    ],
    'max_tokens' => 5,
];

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch) . "\n";
} else {
    echo "Response from OpenAI:\n";
    echo $response . "\n";
}

curl_close($ch);
