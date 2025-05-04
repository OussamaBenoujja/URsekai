                'expires_at' => $banExpires
            ]
        );

        return response()->json(['message' => 'User banned', 'data' => $user]);
    }

    // Call this when a user is warned
    public function warnUser($userId, $reason)
    {
        $user = User::findOrFail($userId);
        // Optionally log the warning somewhere
        $this->notificationService->create(
            $userId,
            'user_warned',
            'You have received a warning: ' . $reason,
            'Account Warning',
            [
                'link' => '/profile'
            ]
        );
        return response()->json(['message' => 'User warned', 'data' => $user]);
    }
}
