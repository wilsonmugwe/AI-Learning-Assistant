<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Material;
use Smalot\PdfParser\Parser;

class AIController extends Controller
{
    /**
     * Handles the uploaded file, extracts content, summarizes with OpenAI, and saves to database.
     */
    public function uploadAndSummarize(Request $request)
    {
        Log::info('AIController: uploadAndSummarize called');

        // Validate input: allow only txt or pdf files, max size 5MB
        $request->validate([
            'file' => 'required|mimes:txt,pdf|max:5120',
        ]);

        // Store the uploaded file with a unique filename
        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/materials', $filename);

        // Extract and clean file text content
        $text = $this->extractText($file);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $text = substr($text, 0, 4000); // Limit text to avoid exceeding token limits

        Log::info('Extracted text length: ' . strlen($text));

        // If text is too short, skip summarization
        if (strlen($text) < 30) {
            $summary = 'Content too short for meaningful summary.';
            $bulletPoints = [];
            Log::info('Skipped OpenAI call due to short content');
        } else {
            // Attempt OpenAI summarization with retries
            [$summary, $bulletPoints] = $this->callOpenAIWithRetry($text, 2);
        }

        // If summarization failed, return an error and don't save
        if (Str::startsWith($summary, 'Summary failed')) {
            Log::warning('Skipping save: OpenAI failed.');
            return response()->json([
                'error' => 'Summarization failed. Please try again later.',
            ], 500);
        }

        // Log final summary and bullets
        Log::info('Final summary to save:', ['summary' => $summary]);
        Log::info('Final bullets to save:', ['bullets' => $bulletPoints]);

        // Save material to database
        $material = Material::create([
            'filename' => $filename,
            'content' => $text,
            'summary' => $summary,
            'bullet_summary' => $bulletPoints ?: null,
        ]);

        Log::info('Material created with ID: ' . $material->id);

        // Return summary and ID to frontend
        return response()->json([
            'material_id' => $material->id,
            'filename' => $filename,
            'summary' => $summary,
            'bullet_summary' => $bulletPoints,
        ]);
    }

    /**
     * Extracts raw text from .txt or .pdf file.
     */
    private function extractText($file): string
    {
        $extension = $file->getClientOriginalExtension();
        Log::info('Extracting text from file type: ' . $extension);

        if ($extension === 'txt') {
            return file_get_contents($file->getRealPath());
        }

        if ($extension === 'pdf') {
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                return $pdf->getText();
            } catch (\Exception $e) {
                Log::error('PDF parsing failed', ['error' => $e->getMessage()]);
                return '[Error parsing PDF]';
            }
        }

        return '[Unsupported file type]';
    }

    /**
     * Retry OpenAI call up to $maxAttempts if it fails.
     */
    private function callOpenAIWithRetry(string $text, int $maxAttempts): array
    {
        $attempt = 0;
        do {
            $attempt++;
            Log::info("OpenAI call attempt $attempt");

            [$summary, $bulletPoints] = $this->callOpenAI($text);

            if (strpos($summary, 'Summary failed') === false) {
                return [$summary, $bulletPoints];
            }

            Log::warning("OpenAI call attempt $attempt failed.");
        } while ($attempt < $maxAttempts);

        return [$summary, $bulletPoints];
    }

    /**
     * Calls OpenAI API to get summary and bullets.
     */
    private function callOpenAI(string $text): array
    {
        $apiKey = config('services.openai.key');

        if (empty($apiKey)) {
            Log::error('OpenAI API key is missing.');
            return ['Summary failed: API key missing.', null];
        }

        // Craft prompt with clear structure
        $prompt = "Please summarize the following content in two parts:\n\n" .
                  "1. A detailed and informative paragraph summary that covers key points thoroughly.\n" .
                  "2. Exactly 3 to 5 bullet points, each starting with a dash (-) and on a new line.\n\n" .
                  "Example:\n" .
                  "This is a detailed summary paragraph that explains the main ideas clearly and with depth.\n\n" .
                  "- Bullet point one\n" .
                  "- Bullet point two\n" .
                  "- Bullet point three\n\n" .
                  "Now summarize the content below:\n$text";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an AI summarizer.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1000,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['Summary failed: API returned error.', null];
            }

            $content = trim($response->json('choices.0.message.content'));
            Log::info('OpenAI raw response content:', ['content' => $content]);

            if (!$content) {
                Log::warning('OpenAI returned empty content.');
                return ['Summary failed: Empty result.', null];
            }

            return $this->parseBulletSummary($content);

        } catch (\Exception $e) {
            Log::error('Exception during OpenAI API call', ['error' => $e->getMessage()]);
            return ['Summary failed: Exception thrown.', null];
        }
    }

    /**
     * Parses OpenAI response into a paragraph and bullet points.
     */
    private function parseBulletSummary(string $content): array
    {
        if (empty($content)) {
            return [null, null];
        }

        $content = trim($content);

        // Try to split into paragraph and bullet block
        $parts = preg_split("/\n\s*\n/", $content, 2);
        $fullSummary = $parts[0] ?? null;
        $bulletBlock = $parts[1] ?? null;

        $bullets = [];

        if ($bulletBlock) {
            $rawBullets = array_filter(array_map('trim', explode("\n", $bulletBlock)));
            foreach ($rawBullets as $line) {
                $cleanLine = preg_replace('/^[-\d. ]+/', '', $line);
                if ($cleanLine !== '') {
                    $bullets[] = $cleanLine;
                }
            }
        }

        // If bullet parsing fails, return fallback
        if (empty($bullets)) {
            Log::warning('No short summary parsed.', ['raw' => $content, 'summary' => $fullSummary]);
            $fallbackBullets = explode("\n", $content);
            return [$fullSummary ?? $content, $fallbackBullets];
        }

        Log::info('Parsed bullets:', ['bullets' => $bullets, 'raw_content' => $content]);

        return [$fullSummary ?? $content, $bullets];
    }
}
