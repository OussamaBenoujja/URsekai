        }
        // Map notification types to keys in preferences
        $map = [
            'game_approved' => 'game_status',
            'game_rejected' => 'game_status',
            'game_featured' => 'game_status',
            'game_deleted' => 'game_status',
            'game_status_changed' => 'game_status',
        ];
        $prefKey = $map[$type] ?? $type;
        return isset($user->notification_preferences[$prefKey])
            ? (bool)$user->notification_preferences[$prefKey]
            : true;
    }
}
