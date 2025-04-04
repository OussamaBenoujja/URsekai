            },
            'achievements',
            'leaderboards'
        ])->where('is_featured', true);

        // Optional: filter by published/approved if needed
        if ($request->filled('is_published')) {
            $query->where('is_published', filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('is_approved')) {
            $query->where('is_approved', filter_var($request->input('is_approved'), FILTER_VALIDATE_BOOLEAN));
        }

        $query->orderBy('updated_at', 'desc');
        $games = $query->paginate($request->input('per_page', 20));
        return $this->success($games);
    }

    /**
     * Helper to check if a developer's user has enabled a notification type.
     */
    private function userWantsNotification($user, $type)
    {
        if (!is_array($user->notification_preferences)) {
            return true; // Default to true if not set
