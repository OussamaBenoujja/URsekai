        if (strpos($filePath, '/storage') === 0) {
            $filePath = substr($filePath, strlen('/storage'));
        }
        $zipPath = storage_path('app/public' . $filePath);
        $extractPath = storage_path("app/public/games/{$gameId}/assets/{$assetId}");
        // If not extracted, extract the zip
        if (!is_dir($extractPath) || !file_exists($extractPath . '/index.html')) {
            if (!file_exists($zipPath)) {
                return $this->error('Game asset file missing', 404);
            }
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === TRUE) {
                $zip->extractTo($extractPath);
                $zip->close();
            } else {
                return $this->error('Failed to extract game asset', 500);
            }
        }
        $testUrl = url("/game-test/{$gameId}/{$assetId}/index.html");
        return $this->success(['test_url' => $testUrl]);
    }
}
