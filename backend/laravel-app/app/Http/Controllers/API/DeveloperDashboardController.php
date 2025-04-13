                    "total_ratings" => $ratingsCount,
                    "average_rating" => $averageRating,
                    "recent_reviews" => $recentReviews,
                ],
                "api" => [
                    "total_calls" => $apiCalls,
                ],
                "webhooks" => $webhooks,
                "support" => [
                    "tickets_total" => $supportTickets,
                    "tickets_pending" => $pendingTickets,
                ],
                "community" => [
                    "forum_threads" => $forumThreads,
                    "forum_posts" => $forumPosts,
                    "friends" => $friendsCount,
                    "feedback_count" => $feedbackCount,
                ],
                "leaderboards" => $leaderboardStats,
                "analytics" => [
                    "country" => $countryData,
                    "device" => $deviceData,
                    "browser" => $browserData,
                ],
            ],
        ]);
    }
}
