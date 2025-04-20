            "totalPlayers" => $totalPlayers,
            "totalRevenue" => $totalRevenue,
            "averagePlaytime" => $formattedPlaytime,
            "totalPlaytime" => $totalPlaytime,
            "numberOfSessions" => $numberOfSessions,
            "topPlayer" => $topPlayer ? [
                "player_id" => $topPlayer->player_id,
                "totalPlaytime" => $topPlayer->total_playtime
            ] : null,
            "firstPlayed" => $firstPlayed,
            "lastPlayed" => $lastPlayed,
        ]);
    }
}
