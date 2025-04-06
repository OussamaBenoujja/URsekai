<?php

use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return ["Laravel" => app()->version()];
});

// Serve the test game files (extracted from ZIP)
Route::get("/game-test/{gameId}/{assetId}/{path?}", function (
    $gameId,
    $assetId,
    $path = "index.html"
) {
    // Support file_path that starts with /storage
    $basePath = "app/public";
    $storagePrefix = "storage/games/{$gameId}/assets/{$assetId}";
    $altPrefix = "games/{$gameId}/assets/{$assetId}";
    
    // Try with /storage prefix first (matches DB file_path)
    $fullPath = storage_path("$basePath/$storagePrefix/$path");
    if (!file_exists($fullPath)) {
        // Fallback to path without /storage prefix
        $fullPath = storage_path("$basePath/$altPrefix/$path");
    }
    if (!file_exists($fullPath)) {
        abort(404);
    }

    // Determine the MIME type
    $mimeType = \Illuminate\Support\Facades\File::mimeType($fullPath);

    // Set headers
    $headers = ["Content-Type" => $mimeType];

    // If the file is gzip-compressed, set Content-Encoding: gzip
    if (preg_match('/\\.gz$/i', $fullPath)) {
        $headers["Content-Encoding"] = "gzip";
    }

    // Return the file with appropriate headers
    return response()->file($fullPath, $headers);
})->where("path", ".*");

require __DIR__ . "/auth.php";
