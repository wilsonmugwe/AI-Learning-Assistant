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
     * Handles file upload, content extraction, summarization via OpenAI, and database storage.
     */
    public function upload(Request $request)
    {
        Log::info('--- Upload request initiated ---');

        try {
            // Step 1: Validate the uploaded file
            Log::info('Validating request file input...');
            $request->validate([
                'file' => 'required|mimes:txt,pdf|max:5120',
            ]);
            Log::info('File validation passed');

            // Step 2: Save file with unique name
            $file = $request->file('file');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/materials', $filename);
            Log::info('File stored successfully', ['filename' => $filename]);

            // Step 3: Extract and clean text
            Log::info('Extracting text from file...');
            $text = $this->extractText($file);
            Log::info('Raw extracted text length', ['length' => strlen($text)]);

            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            $text = substr($text, 0, 4000);
            Log::info('Text cleaned and trimmed', ['final_length' => strlen($text)]);

            // Step 4: Check if content is long enough to summarize
            if (strlen($text) < 30) {
                Log::info('Text too short for OpenAI. Saving minimal record.');
                $material = Material::create([
                    'filename' => $filename,
                    'content' => $text,
                    'summary' => 'Content too short to summarize.',
                    'bullet_summary' => null,
                ]);
                return response()->json([
                    'material_id' => $material->id,
                    'filename' => $filename,
                    'summary' => 'Content too short to summarize.',
                    'bullet_summary' => null,
                ]);
            }

            // Step 5: Send to OpenAI for summarization
            Log::info('Sending to OpenAI for summarization...');
            [$fullSummary, $bulletSummary] = $this->summarizeBoth($text);

            // Step 6: Retry if first response failed
            if (
                $fullSummary === 'Summary failed: Empty result.' ||
                $fullSummary === 'Summary failed: API returned error.'
            ) {
                Log::warning('Retrying summarization after failure...');
                [$fullSummary, $bulletSummary] = $this->summarizeBoth($text);
            }

            // Step 7: Save all to DB
            Log::info('Saving summarized content to database...');
            $material = Material::create([
                'filename' => $filename,
                'content' => $text,
                'summary' => $fullSummary,
                'bullet_summary' => $bulletSummary,
            ]);
            Log::info('Saved successfully', ['material_id' => $material->id]);

            // Step 8: Return response
            return response()->json([
                'material_id' => $material->id,
                'filename' => $filename,
                'summary' => $fullSummary,
                'bullet_summary' => $bulletSummary,
            ]);
        } catch (\Throwable $e) {
            // Global error capture
            Log::error('Unhandled error in upload process', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return error for debugging in Postman
            return response()->json([
                'summary' => 'Summary failed: ' . $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ], 500);
        }
    }

    /**
     * Extract plain text from uploaded .txt or .pdf file.
     */
    private function extractText($file): string
    {
        $extension = $file->getClientOriginalExtension();
        Log::info('Preparing to extract text', ['extension' => $extension]);

        if ($extension === 'txt') {
            Log::info('Reading plain text file');
            return file_get_contents($file->getRealPath());
        }

        if ($extension === 'pdf') {
            Log::info('Parsing PDF file');
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                $text = $pdf->getText();
                Log::info('PDF parsed successfully');
                return $text;
            } catch (\Exception $e) {
                Log::error('PDF parse failure', ['error' => $e->getMessage()]);
                return '[Error parsing PDF]';
            }
        }

        Log::warning('Unsupported file extension encountered');
        return '[Unsupported file type]';
    }

    /**
     * Summarize content via OpenAI. Returns paragraph and bullet summary.
     */
    private function summarizeBoth(string $text): array
    {
        $apiKey = env('OPENAI_API_KEY');
        Log::info('Preparing OpenAI call', ['api_key_loaded' => !empty($apiKey)]);

        if (empty($apiKey)) {
            Log::error('OpenAI API key is not set in environment');
            return ['Summary failed: API key missing.', null];
        }

        $prompt = "Summarize the following content into two parts:\n\n" .
                  "1. A concise paragraph summary.\n" .
                  "2. 3 to 5 bullet points, each starting with a dash (-).\n\n" .
                  "Content:\n$text";

        try {
            Log::info('Sending request to OpenAI...');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an AI summarizer.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 500,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI response not successful', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['Summary failed: API returned error.', null];
            }

            $content = trim($response->json('choices.0.message.content'));
            Log::info('Received content from OpenAI', ['length' => strlen($content)]);

            if (!$content) {
                Log::warning('OpenAI returned empty content');
                return ['Summary failed: Empty result.', null];
            }

            $parts = preg_split('/\n\s*\n/', $content, 2);
            $full = trim($parts[0] ?? '');
            $bulletsRaw = trim($parts[1] ?? '');
            $bulletLines = preg_split('/\r\n|\r|\n/', $bulletsRaw);
            $bulletPoints = array_filter($bulletLines, fn($line) =>
                preg_match('/^\s*[-•]/', $line)
            );

            if (empty($bulletPoints)) {
                Log::warning('No valid bullet points found');
                return [$full, null];
            }

            $cleanBullets = implode("\n", array_map('trim', $bulletPoints));
            Log::info('Bullet points extracted', ['count' => count($bulletPoints)]);

            return [$full, $cleanBullets];
        } catch (\Exception $e) {
            Log::error('OpenAI call exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['Summary failed: Exception thrown.', null];
        }
    }
}
