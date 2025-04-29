        $totalRatings = GameReview::where('game_id', $gameId)
                                ->where('is_hidden', false)
                                ->count();
                                
        $game = Game::find($gameId);
        
        if ($game) {
            $game->average_rating = $avgRating ?? 0;
            $game->total_ratings = $totalRatings;
            $game->save();
        }
    }
}
