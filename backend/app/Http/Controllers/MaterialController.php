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
     * Returns a trimmed-down dataset: only id, filename, title, summary, and created_at.
     * Used to display a list of uploaded materials to the frontend.
     */
    public function index(): JsonResponse
    {
        // Get latest materials from DB with selected fields
        $materials = Material::latest()->get([
            'id',         // unique identifier for routing
            'filename',   // original or generated filename
            'title',      // optional title field
            'summary',    // paragraph-style summary from OpenAI
            'created_at'  // timestamp for sorting/display
        ]);

        // Return the list as a JSON response
        return response()->json($materials);
    }

    /**
     * GET /api/summaries/{id}
     * Fetches a single material by ID and returns its full and bullet summaries.
     * Used by the summary viewer or Q&A screen to load a specific material.
     */
    public function show($id): JsonResponse
    {
        // Attempt to find the material by ID
        // Will throw 404 if not found
        $material = Material::findOrFail($id);

        // bullet_summary is expected to be cast as array in the Material model
        $shortSummary = $material->bullet_summary ?? [];

        // Log a warning if short summary is empty or missing
        if (empty($shortSummary)) {
            Log::warning("No short summary parsed for material ID {$id}", [
                'raw' => $material->bullet_summary,   // raw DB value
                'summary' => $material->summary       // paragraph summary (always returned)
            ]);
        }

        // Return both the long (paragraph) and short (bullet) summaries
        return response()->json([
            'long_summary' => $material->summary,
            'short_summary' => $shortSummary,
        ]);
    }
}
