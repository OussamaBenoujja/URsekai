                'total_days' => $startDate->diffInDays($endDate) + 1,
            ],
        ];

        return $this->success($response);
    }

    /**
     * Helper method to get the developer profile for the authenticated user.
     *
     * @return Developer|null
     */
    private function getDeveloperProfile()
    {
        $user = Auth::user();
        
        // Check if user has developer role
        if ($user->role !== 'developer' && $user->role !== 'admin') {
            return null;
        }
        
        // Get developer profile
        return Developer::where('user_id', $user->user_id)->first();
    }
}
