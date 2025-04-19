        $firstPlayed = GameSession::where('game_id', $gameId)
            ->orderBy('created_at', 'asc')
            ->value('created_at');

        // Last played date
        $lastPlayed = GameSession::where('game_id', $gameId)
            ->orderBy('created_at', 'desc')
            ->value('created_at');

        // Convert average playtime 
        $hours = floor($averagePlaytime / 60); // Convert minutes to hours
        $minutes = $averagePlaytime % 60; // Remaining minutes
        $formattedPlaytime = "{$hours}h {$minutes}m";

        // Include game details (using thumbnail_url directly)
        $gameDetails = [
            "name" => $game->name,
            "description" => $game->description,
            "short_description" => $game->short_description,
            "thumbnail" => $game->thumbnail_url ?? null,
            "images" => $game->images,
        ];

        return response()->json([
            "gameDetails" => $gameDetails,
