<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\AIController;

// Upload a new material (PDF or text)
Route::post('/upload', [UploadController::class, 'upload']);

// Get all summaries (list)
Route::get('/summaries', [MaterialController::class, 'index']);

// Get a specific summary by ID (long + bullet)
Route::get('/summaries/{id}', [MaterialController::class, 'show']);

// Ask a question based on a material
Route::post('/question', [QuestionController::class, 'ask']);
Route::get('/question', function () {
    return response()->json(['message' => 'GET not supported, use POST']);
});

// AI-powered upload and summarize
Route::post('/ai/upload-and-summarize', [AIController::class, 'uploadAndSummarize']);

// Manually trigger bullet point parsing (for testing/debugging)
Route::post('/ai/parse-bullet-summary/{id}', [AIController::class, 'parseBulletSummary']);
