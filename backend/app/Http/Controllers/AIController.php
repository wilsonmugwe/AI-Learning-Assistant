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
    public function uploadAndSummarize(Request $request)
    {
        Log::info('[AIController] uploadAndSummarize() triggered');

        $request->validate([
            'file' => 'required|mimes:txt,pdf|max:5120',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $storedPath = $file->storeAs('public/materials', $filename);

        Log::info("[File Uploaded] Name: $originalName | Type: $extension | Saved As: $storedPath");

        $text = $this->extractText($file);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $text = substr($text, 0, 4000);

        if (strlen($text) < 30) {
            Log::warning('[Skipped] Text too short for summarization.');
            $summary = 'Content too short for meaningful summary.';
            $bulletPoints = [];
        } else {
            Log::info('[OpenAI] Valid text. Calling API...');
            [$summary, $bulletPoints] = $this->callOpenAIWithRetry($text, 2);
        }

        if (Str::startsWith($summary, 'Summary failed')) {
            Log::error('[OpenAI Error] Summary failed. Skipping save.');
            return response()->json(['error' => 'OpenAI summarization failed.'], 500);
        }

        $material = Material::create([
            'filename' => $filename,
            'content' => $text,
            'summary' => $summary,
            'bullet_summary' => implode("\n", $bulletPoints), // Save as newline-delimited string
        ]);

        Log::info('[Database] Material saved.', ['id' => $material->id]);

        return response()->json([
            'material_id' => $material->id,
            'filename' => $filename,
            'summary' => $summary,
            'bullet_summary' => $bulletPoints,
        ]);
    }

    private function extractText($file): string
    {
        $extension = $file->getClientOriginalExtension();

        if ($extension === 'txt') {
            return file_get_contents($file->getRealPath());
        }

        if ($extension === 'pdf') {
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                return $pdf->getText();
            } catch (\Exception $e) {
                Log::error('[PDF Parsing] Error', ['error' => $e->getMessage()]);
                return '';
            }
        }

        Log::warning("[Unsupported] File type: $extension");
        return '';
    }

    private function callOpenAIWithRetry(string $text, int $maxAttempts): array
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            Log::info("[OpenAI] Attempt $attempt...");
            [$summary, $bullets] = $this->callOpenAI($text);

            if (!Str::startsWith($summary, 'Summary failed')) {
                return [$summary, $bullets];
            }
        }

        return [$summary, $bullets];
    }

    private function callOpenAI(string $text): array
    {
        $apiKey = config('services.openai.key');
        if (!$apiKey) {
            Log::error('[OpenAI] Missing API key.');
            return ['Summary failed: API key missing.', []];
        }

        $prompt = <<<PROMPT
Please summarize the following content in two parts:

1. A detailed paragraph summary.
2. 3 to 5 bullet points, each starting with a dash (-) on a new line.

$text
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $apiKey",
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
                Log::error('[OpenAI] Failed response', ['status' => $response->status()]);
                return ['Summary failed: OpenAI API error.', []];
            }

            $content = trim($response->json('choices.0.message.content') ?? '');
            return $this->parseBulletSummary($content);

        } catch (\Exception $e) {
            Log::error('[OpenAI] Exception', ['message' => $e->getMessage()]);
            return ['Summary failed: Exception thrown.', []];
        }
    }

    private function parseBulletSummary(string $content): array
    {
        if (empty($content)) return ['Summary failed: Empty content.', []];

        $parts = preg_split("/\n\s*\n/", trim($content), 2);
        $fullSummary = $parts[0] ?? $content;
        $bulletBlock = $parts[1] ?? '';

        $bullets = [];

        if ($bulletBlock) {
            $lines = explode("\n", $bulletBlock);
            foreach ($lines as $line) {
                $clean = preg_replace('/^[-\d. ]+/', '', trim($line));
                if ($clean) $bullets[] = $clean;
            }
        }

        return [$fullSummary, $bullets];
    }

    public function show($id)
    {
        $material = Material::find($id);
        if (!$material) {
            Log::error("[Show] Material $id not found.");
            return response()->json(['error' => 'Material not found.'], 404);
        }

        $summary = trim($material->summary ?? '');
        $bulletSummary = [];

        if (is_string($material->bullet_summary)) {
            $bulletSummary = array_filter(array_map('trim', preg_split('/[\r\n]+/', $material->bullet_summary)));
        }

        return response()->json([
            'data' => [
                'summary' => $summary,
                'bullet_summary' => $bulletSummary,
            ]
        ]);
    }
}
