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

        // Validate input: only allow txt or pdf files, max size 5MB
        $request->validate([
            'file' => 'required|mimes:txt,pdf|max:5120',
        ]);

        // Save uploaded file to storage with a unique filename
        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/materials', $filename);

        // Extract the raw text content from the file
        $text = $this->extractText($file);
        $text = preg_replace('/\s+/', ' ', $text); // Collapse multiple whitespaces
        $text = trim($text);
        $text = substr($text, 0, 4000); // Limit text to avoid exceeding OpenAI token limits

        Log::info('Extracted text length: ' . strlen($text));

        // If the content is too short, skip calling OpenAI
        if (strlen($text) < 30) {
            $summary = 'Content too short for meaningful summary.';
            $bulletPoints = [];
            Log::info('Skipped OpenAI call due to short content');
        } else {
            // Try to get summary from OpenAI (with retry logic)
            [$summary, $bulletPoints] = $this->callOpenAIWithRetry($text, 2);
        }

        // Save the new material entry to the database
        $material = Material::create([
            'filename' => $filename,
            'content' => $text,
            'summary' => $summary,
            'bullet_summary' => $bulletPoints ?: null, // save bullet points if any
        ]);

        Log::info('Material created with ID: ' . $material->id);

        // Return the summary data as JSON response
        return response()->json([
            'material_id' => $material->id,
            'filename' => $filename,
            'summary' => $summary,
            'bullet_summary' => $bulletPoints,
        ]);
    }

    /**
     * Extracts the text content from a .txt or .pdf file.
     */
    private function extractText($file): string
    {
        Log::info('Extracting text from file');

        $extension = $file->getClientOriginalExtension();

        // For .txt files, just read the contents
        if ($extension === 'txt') {
            return file_get_contents($file->getRealPath());
        }

        // For .pdf files, use Smalot PDF Parser
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

        // Unsupported file type fallback
        return '[Unsupported file type]';
    }

    /**
     * Tries to call OpenAI up to $maxAttempts times if previous attempts fail.
     */
    private function callOpenAIWithRetry(string $text, int $maxAttempts): array
    {
        $attempt = 0;
        do {
            $attempt++;
            Log::info("OpenAI call attempt $attempt");

            [$summary, $bulletPoints] = $this->callOpenAI($text);

            // Only return result if it didn't fail
            if (strpos($summary, 'Summary failed') === false) {
                return [$summary, $bulletPoints];
            }

            Log::warning("OpenAI call attempt $attempt failed, retrying...");
        } while ($attempt < $maxAttempts);

        // Return last result even if it failed
        return [$summary, $bulletPoints];
    }

    /**
     * Calls OpenAI's Chat API to get both full paragraph summary and bullet points.
     */
    private function callOpenAI(string $text): array
    {
        $apiKey = env('OPENAI_API_KEY');

        // Check if API key is configured
        if (empty($apiKey)) {
            Log::error('OpenAI API key is missing.');
            return ['Summary failed: API key missing.', null];
        }

        // Prompt tells the AI exactly how to format the response
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
            // Send the request to OpenAI's API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an AI summarizer.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1000, // Limit to prevent overflows
            ]);

            // Handle failure case
            if (!$response->successful()) {
                Log::error('OpenAI request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['Summary failed: API returned error.', null];
            }

            // Extract content from response
            $content = trim($response->json('choices.0.message.content'));
            Log::info('OpenAI raw response content:', ['content' => $content]);

            // Handle empty response
            if (!$content) {
                Log::warning('OpenAI returned empty content.');
                return ['Summary failed: Empty result.', null];
            }

            // Parse the combined summary (paragraph + bullets)
            return $this->parseBulletSummary($content);

        } catch (\Exception $e) {
            Log::error('Exception during OpenAI API call', ['error' => $e->getMessage()]);
            return ['Summary failed: Exception thrown.', null];
        }
    }

    /**
     * Splits the full OpenAI response into a paragraph and array of bullet points.
     */
    private function parseBulletSummary(string $content): array
    {
        if (empty($content)) {
            return [null, null];
        }

        // Clean and normalize the input
        $content = trim($content);

        // Split into two parts: full summary and bullet block
        $parts = preg_split("/\n\s*\n/", $content, 2);
        $fullSummary = $parts[0] ?? null;
        $bulletBlock = $parts[1] ?? null;

        $bullets = [];

        // If bullets exist, split into lines and clean them
        if ($bulletBlock) {
            $rawBullets = array_filter(array_map('trim', explode("\n", $bulletBlock)));

            foreach ($rawBullets as $line) {
                // Remove dash or numbering prefix
                $cleanLine = preg_replace('/^[-\d. ]+/', '', $line);
                if ($cleanLine !== '') {
                    $bullets[] = $cleanLine;
                }
            }
        }

        Log::info('Parsed bullets:', ['bullets' => $bullets, 'raw_content' => $content]);

        // If no bullets parsed, just return full summary
        if (empty($bullets)) {
            Log::warning('No short summary parsed.', ['raw' => $content, 'summary' => $fullSummary]);
            return [$fullSummary ?? $content, null];
        }

        return [$fullSummary ?? $content, $bullets];
    }
}
