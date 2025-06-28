<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{
    /**
     * Handles the incoming POST /api/question request.
     * Validates input, fetches the related material, prepares a prompt,
     * sends it to OpenAI, and returns the generated answer.
     */
    public function ask(Request $request)
    {
        Log::info('[ask] Question API called.');

        // Validate request input: question must be a string, material_id must exist
        $request->validate([
            'question' => 'required|string',
            'material_id' => 'required|integer|exists:materials,id',
        ]);

        // Extract question from request
        $question = $request->input('question');
        Log::info('[ask] Received question: ' . $question);

        // Fetch the corresponding material from the database
        $material = Material::findOrFail($request->input('material_id'));
        Log::info('[ask] Fetched material: ID=' . $material->id);

        // Get the full text content from the material
        $content = $material->content;
        Log::info('[ask] Material content length: ' . strlen($content));

        // Handle empty or invalid content
        if (empty(trim($content))) {
            Log::error('[ask] Material content is empty or whitespace.');
            return response()->json(['error' => 'Material content is empty.'], 400);
        }

        // Prepare the OpenAI prompt using both content and the question
        $prompt = "Context:\n" . $content . "\n\nQuestion: " . $question;
        Log::info('[ask] Prompt prepared. Length: ' . strlen($prompt));

        // Warn if the prompt is longer than typical token limits
        if (strlen($prompt) > 4000) {
            Log::warning('[ask] Prompt length exceeds typical API limits, consider shortening material content.');
        }

        // Call OpenAI and get the response
        Log::info('[ask] Calling OpenAI with prompt...');
        $answer = $this->callOpenAI($prompt);

        // Check if answer is valid
        if (empty($answer) || stripos($answer, 'error') !== false) {
            Log::error('[ask] Error or empty answer received from OpenAI.', ['answer' => $answer]);
            return response()->json(['error' => 'Answer generation failed.'], 500);
        }

        Log::info('[ask] Answer received successfully.');

        // Return the generated answer in the response
        return response()->json([
            'answer' => $answer,
        ]);
    }

    /**
     * Makes a POST request to OpenAI Chat API using the provided prompt.
     * Returns the generated answer as a string.
     */
    private function callOpenAI($prompt)
    {
        Log::info('[callOpenAI] Sending prompt to OpenAI...');

        // Get OpenAI API key from .env
        $apiKey = env('OPENAI_API_KEY');

        // Fail early if key is missing
        if (!$apiKey) {
            Log::error('[callOpenAI] Missing OpenAI API key.');
            return 'API key is missing.';
        }

        // Prepare payload for chat completion
        $payload = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Answer the question based on the provided context.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 300, // Controls how long the answer can be
        ];

        // Log payload for debugging
        Log::debug('[callOpenAI] Payload:', $payload);

        // Make POST request to OpenAI API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', $payload);

        // Log raw response details
        Log::debug('[callOpenAI] OpenAI API status: ' . $response->status());
        Log::debug('[callOpenAI] OpenAI API response body: ' . $response->body());

        // If successful, extract content from response
        if ($response->successful()) {
            Log::info('[callOpenAI] OpenAI responded successfully.');
            $content = $response->json('choices.0.message.content');
            Log::debug('[callOpenAI] Response content:', ['content' => $content]);
            return $content;
        } else {
            // Log failure details
            Log::error('[callOpenAI] API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return 'Answer generation failed.';
        }
    }
}
