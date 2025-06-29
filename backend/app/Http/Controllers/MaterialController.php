<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MaterialController extends Controller
{
    /**
     * GET /api/summaries
     * Fetches a list of all materials ordered by most recent.
     * Returns trimmed data: id, filename, title, summary, and created_at.
     */
    public function index(): JsonResponse
    {
        $materials = Material::latest()->get([
            'id',
            'filename',
            'title',
            'summary',
            'created_at'
        ]);

        return response()->json($materials);
    }

    /**
     * GET /api/summaries/{id}
     * Returns one material with paragraph summary and newline-encoded bullet summary.
     */
    public function show($id): JsonResponse
    {
        $material = Material::findOrFail($id);

        $paragraph = $material->summary ?? "";

        // Normalize bullet summary
        $bulletData = $material->bullet_summary ?? [];

        // Convert array to string with line breaks
        $bulletString = is_array($bulletData)
            ? implode("\n", array_filter($bulletData))
            : (string) $bulletData;

        // Escape real newlines for frontend parsing
        $escapedBulletString = str_replace("\n", "\\n", $bulletString);

        // Optional: log if bullet summary is empty
        if (empty($escapedBulletString)) {
            Log::warning("Empty bullet summary for material ID {$id}", [
                'raw_bullet_summary' => $material->bullet_summary,
                'paragraph_summary' => $paragraph,
            ]);
        }

        return response()->json([
            'summary' => trim($paragraph),
            'bullet_summary' => $escapedBulletString,
        ]);
    }
}
