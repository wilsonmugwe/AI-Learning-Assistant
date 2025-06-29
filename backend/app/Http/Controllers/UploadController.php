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
    public function upload(Request $request)
    {
        try {
            Log::info('[UPLOAD] Incoming upload request');

            $request->validate([
                'file' => 'required|mimes:txt,pdf|max:5120',
            ]);

            $file = $request->file('file');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/materials', $filename);
            Log::info('[UPLOAD] File stored', ['filename' => $filename]);

            $text = $this->extractText($file);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            $text = substr($text, 0, 4000);
            Log::info('[UPLOAD] Text extracted', ['length' => strlen($text)]);

            if (strlen($text) < 30) {
                Log::warning('[UPLOAD] Text too short to summarize', ['length' => strlen($text)]);
                $material = Material::create([
                    'filename' => $filename,
                    'content' => $text,
                    'summary' => 'Content too short to summarize.',
                    'bullet_summary' => null,
                ]);
                return response()->json([
                    'material_id' => $material->id,
                    'filename' => $filename,
                    'summary' => '[SHORT_TEXT] Content too short to summarize.',
                    'bullet_summary' => null,
                ]);
            }

            Log::info('[UPLOAD] Sending to OpenAI...');
            [$fullSummary, $bulletSummary] = $this->summarizeBoth($text);

            if (str_starts_with($fullSummary, 'Summary failed')) {
                Log::warning('[UPLOAD] First summary attempt failed. Retrying...');
                [$fullSummary, $bulletSummary] = $this->summarizeBoth($text);
            }

            $material = Material::create([
                'filename' => $filename,
                'content' => $text,
                'summary' => $fullSummary,
                'bullet_summary' => $bulletSummary,
            ]);

            Log::info('[UPLOAD] Material saved', ['material_id' => $material->id]);

            return response()->json([
                'material_id' => $material->id,
                'filename' => $filename,
                'summary' => $fullSummary,
                'bullet_summary' => $bulletSummary,
            ]);

        } catch (\Throwable $e) {
            Log::error('[UPLOAD] Fatal error during upload', ['error' => $e->getMessage()]);
            return response()->json([
                'summary' => '[FATAL_UPLOAD_ERROR] ' . $e->getMessage(),
                'bullet_summary' => null,
            ], 500);
        }
    }

    private function extractText($file): string
    {
        try {
            $extension = $file->getClientOriginalExtension();
            Log::info('[TEXT] Extracting text', ['extension' => $extension]);

            if ($extension === 'txt') {
                $content = file_get_contents($file->getRealPath());
                Log::info('[TEXT] TXT content extracted', ['length' => strlen($content)]);
                return $content;
            }

            if ($extension === 'pdf') {
                $parser = new Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                $content = $pdf->getText();
                Log::info('[TEXT] PDF content extracted', ['length' => strlen($content)]);
                return $content;
            }

            Log::error('[TEXT] Unsupported file type');
            return '[ERROR] Unsupported file type.';

        } catch (\Throwable $e) {
            Log::error('[TEXT] Error extracting content', ['error' => $e->getMessage()]);
            return '[ERROR_EXTRACTING_TEXT] ' . $e->getMessage();
        }
    }

    private function summarizeBoth(string $text): array
    {
        $apiKey = env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            Log::error('[AI] Missing API key');
            return ['[AI_KEY_MISSING] Please set your OpenAI API key.', null];
        }

        $prompt = "Summarize the following content into two parts:\n\n" .
                  "1. A concise paragraph summary.\n" .
                  "2. 3 to 5 bullet points, each starting with a dash (-).\n\n" .
                  "Content:\n$text";

        try {
            Log::info('[AI] Sending request to OpenAI', ['text_length' => strlen($text)]);

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
                Log::error('[AI] OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['[AI_API_ERROR] ' . $response->body(), null];
            }

            $content = trim($response->json('choices.0.message.content') ?? '');
            Log::info('[AI] Response received', ['length' => strlen($content)]);

            if (!$content) {
                Log::warning('[AI] Empty content from OpenAI');
                return ['[AI_EMPTY_RESPONSE] No summary returned.', null];
            }

            $parts = preg_split('/\n\s*\n/', $content, 2);
            $full = trim($parts[0] ?? '');
            $bulletsRaw = trim($parts[1] ?? '');

            $bulletLines = preg_split('/\r\n|\r|\n/', $bulletsRaw);
            $bulletPoints = array_filter($bulletLines, fn($line) => preg_match('/^\s*[-•]/', $line));

            if (empty($bulletPoints)) {
                Log::warning('[AI] No bullet points returned');
                return [$full, null];
            }

            $cleanBullets = implode("\n", array_map('trim', $bulletPoints));
            Log::info('[AI] Bullet points parsed', ['count' => count($bulletPoints)]);

            return [$full, $cleanBullets];

        } catch (\Throwable $e) {
            Log::error('[AI] Exception during OpenAI call', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['[AI_EXCEPTION] ' . $e->getMessage(), null];
        }
    }
}
