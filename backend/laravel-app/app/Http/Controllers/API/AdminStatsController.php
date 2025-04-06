        ]);
    }

    // GET /api/v1/admin/stats/platform
    public function platform()
    {
        $newUsersToday = DB::table('users')->whereDate('registration_date', now()->toDateString())->count();
        $newGamesToday = DB::table('games')->whereDate('created_at', now()->toDateString())->count();
        $totalRevenue = DB::table('transactions')->where('status', 'completed')->sum('amount');
        $totalTransactions = DB::table('transactions')->where('status', 'completed')->count();
        return response()->json([
            'new_users_today' => $newUsersToday,
            'new_games_today' => $newGamesToday,
            'total_revenue' => $totalRevenue,
            'total_transactions' => $totalTransactions,
        ]);
    }
}
