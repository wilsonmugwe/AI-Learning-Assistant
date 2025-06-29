<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class UploadController extends Controller
{
    /**
     * Handles file upload, extracts text content, sends it to OpenAI for summarization,
     * and stores the result in the database.
     */
    public function upload(Request $request)
    {
        // Validate that the uploaded file is either .txt or .pdf and less than 5MB
        $request->validate([
            'file' => 'required|mimes:txt,pdf|max:5120',
        ]);

        // Store the uploaded file with a unique filename
        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/materials', $filename);
        Log::info('File uploaded and saved', ['filename' => $filename]);

        // Extract the file content as plain text
        $text = $this->extractText($file);
        $text = preg_replace('/\s+/', ' ', $text); // Clean up whitespace
        $text = trim($text);
        $text = substr($text, 0, 4000); // Limit length to avoid OpenAI token overflow
        Log::info('Text extracted and preprocessed', ['length' => strlen($text)]);

        // Skip OpenAI call if content is too short
        if (strlen($text) < 30) {
            Log::info('Skipped OpenAI summary: text too short.', ['length' => strlen($text)]);

            // Save basic material entry with short message
            $material = Material::create([
                'filename' => $filename,
                'content' => $text,
                'summary' => 'Content too short to summarize.',
                'bullet_summary' => null,
            ]);

            // Return response with default message
            return response()->json([
                'material_id' => $material->id,
                'filename' => $filename,
                'summary' => 'Content too short to summarize.',
                'bullet_summary' => null,
            ]);
        }

        // Try summarizing once, then retry once if result looks invalid
        Log::info('Sending text to OpenAI for summarization...');
        [$fullSummary, $bulletSummary] = $this->summarizeBoth($text);

        if (
            $fullSummary === 'Summary failed: Empty result.' ||
            $fullSummary === 'Summary failed: API returned error.'
        ) {
            Log::warning('Retrying OpenAI summary once after initial failure...');
            [$fullSummary, $bulletSummary] = $this->summarizeBoth($text);
        }

        // Save the material and summaries to the database
        $material = Material::create([
            'filename' => $filename,
            'content' => $text,
            'summary' => $fullSummary,
            'bullet_summary' => $bulletSummary,
        ]);

        Log::info('Summary stored successfully', ['material_id' => $material->id]);

        // Return summary data as JSON response
        return response()->json([
            'material_id' => $material->id,
            'filename' => $filename,
            'summary' => $fullSummary,
            'bullet_summary' => $bulletSummary,
        ]);
    }

    /**
     * Extracts plain text from a .txt or .pdf file.
     * Returns an error message string if extraction fails.
     */
    private function extractText($file): string
    {
        $extension = $file->getClientOriginalExtension();
        Log::info('Extracting text from file', ['extension' => $extension]);

        // Handle plain text files
        if ($extension === 'txt') {
            return file_get_contents($file->getRealPath());
        }

        // Handle PDF files using Smalot\PdfParser
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

        // Unsupported file type
        return '[Unsupported file type]';
    }

    /**
     * Sends text content to OpenAI API to get a paragraph and bullet summary.
     * Returns an array of [paragraph, bullet_summary_text].
     */
    private function summarizeBoth(string $text): array
    {
        $apiKey = env('OPENAI_API_KEY');
        Log::info('Initiating OpenAI summarization', ['api_key_present' => !empty($apiKey), 'text_length' => strlen($text)]);

        // Check for missing API key
        if (empty($apiKey)) {
            Log::error('OpenAI API key is missing.');
            return ['Summary failed: API key missing.', null];
        }

        // Construct prompt for OpenAI
        $prompt = "Summarize the following content into two parts:\n\n" .
                  "1. A concise paragraph summary.\n" .
                  "2. 3 to 5 bullet points, each starting with a dash (-).\n\n" .
                  "Content:\n$text";

        try {
            // Send POST request to OpenAI's chat completions endpoint
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an AI summarizer.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 500, // Set output token limit
            ]);

            // Handle failed response
            if (!$response->successful()) {
                Log::error('OpenAI API call failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['Summary failed: API returned error.', null];
            }

            // Extract and clean content
            $content = trim($response->json('choices.0.message.content'));
            Log::info('OpenAI response content received', ['length' => strlen($content)]);

            if (!$content) {
                Log::warning('OpenAI returned empty content.');
                return ['Summary failed: Empty result.', null];
            }

            // Split result into paragraph + bullets using double line breaks
            $parts = preg_split('/\n\s*\n/', $content, 2);
            $full = trim($parts[0] ?? '');
            $bulletsRaw = trim($parts[1] ?? '');

            // Parse each line of the bullet block
            $bulletLines = preg_split('/\r\n|\r|\n/', $bulletsRaw);
            $bulletPoints = array_filter($bulletLines, fn($line) =>
                preg_match('/^\s*[-•]/', $line) // must start with - or •
            );

            // Handle missing or invalid bullets
            if (empty($bulletPoints)) {
                Log::warning('OpenAI returned no valid bullet points.');
                return [$full, null];
            }

            // Clean and combine bullet lines
            $cleanBullets = implode("\n", array_map('trim', $bulletPoints));
            Log::info('Bullet points parsed successfully', ['count' => count($bulletPoints)]);

            return [$full, $cleanBullets];

        } catch (\Exception $e) {
            // Catch any exceptions (network issues, server errors, etc.)
            Log::error('Exception during OpenAI call', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'api_key_present' => !empty($apiKey),
                'text_length' => strlen($text),
            ]);

            return ['Summary failed: Exception thrown.', null];
        }
    }
}
