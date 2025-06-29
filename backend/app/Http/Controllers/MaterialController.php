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
            'created_at',
        ]);

        return response()->json($materials);
    }

    /**
     * GET /api/summaries/{id}
     * Returns one material with full paragraph summary and bullet summary as an array,
     * assuming bullet_summary is always stored as newline-delimited plain text.
     */
    public function show($id): JsonResponse
    {
        $material = Material::findOrFail($id);

        $summary = trim($material->summary ?? '');

        // Bullet summary is treated as newline-separated string only
        $bulletSummary = [];
        if (is_string($material->bullet_summary)) {
            $bulletSummary = array_filter(
                array_map('trim', preg_split('/[\r\n]+/', $material->bullet_summary))
            );
        }

        Log::info("Returning summary for material ID $id", [
            'summary' => $summary,
            'bullet_summary' => $bulletSummary,
        ]);

        return response()->json([
            'data' => [
                'summary' => $summary,
                'bullet_summary' => $bulletSummary,
            ]
        ]);
    }
}
