<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserProfileController;
use App\Http\Controllers\API\FriendshipController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\GameController;
use App\Http\Controllers\API\GameReviewController;
use App\Http\Controllers\API\DeveloperGameController;
use App\Http\Controllers\API\DeveloperGameAchievementController;
use App\Http\Controllers\API\DeveloperGameLeaderboardController;
use App\Http\Controllers\API\DeveloperGameAnalyticsController;
use App\Http\Controllers\API\AdminGameController;
use App\Http\Controllers\API\AdminGameCategoryController;
use App\Http\Controllers\API\AdminGameTagController;
use App\Http\Controllers\API\GameTestController;
use App\Http\Controllers\API\DeveloperDashboardController;
use App\Http\Controllers\API\DeveloperGameMetricsController;
use App\Http\Controllers\API\DeveloperNotificationController;
use App\Http\Controllers\API\DeveloperFollowerController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\AdminUserController;
use App\Http\Controllers\API\AdminStatsController;
use App\Http\Controllers\API\PlatformUpdateController;
use App\Http\Controllers\API\NewsletterController;
use App\Http\Controllers\API\SearchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// API v1 prefix for all routes
Route::prefix('v1')->group(function () {

            // Public endpoint to get screenshots for a game (must be outside the games prefix group!)
            Route::get('/games/{gameId}/screenshots', [GameController::class, 'getScreenshots']);
            // Public endpoint to get age ratings for games (must be outside the games prefix group!)
            Route::get('/games/age-ratings', [GameController::class, 'ageRatings']);
            // Add public endpoint for game assets (for user portal)
            Route::get('/games/{gameId}/assets', [GameController::class, 'publicAssets']);
            // Add public endpoint for preparing a game asset for play
            Route::post('/games/{gameId}/assets/{assetId}/prepare-play', [
                App\Http\Controllers\API\GameController::class,
                'preparePlay',
            ]);

    // Public routes
    Route::group(["prefix" => "auth"], function () {
        Route::post("login", [AuthController::class, "login"]);
        Route::post("register", [AuthController::class, "register"]);
        // Password reset routes
        Route::post("forgot-password", [AuthController::class, "forgotPassword"]);
        Route::post("reset-password", [AuthController::class, "resetPassword"]);
    });

    // Public route to list developers
    Route::get('/developers', [UserProfileController::class, 'listDevelopers']);

    // Public route for recommended developers
    Route::get("/developers/recommended", [UserProfileController::class, "recommendedDevelopers"]);

    // Public user profiles
    Route::get("/users/{id}", [UserProfileController::class, "show"]);
    Route::get('/profile/{username}', [UserProfileController::class, 'showByUsername']);
    Route::get('/profile/{username}/achievements', [UserProfileController::class, 'achievementsByUsername']);
    Route::get('/profile/{username}/badges', [UserProfileController::class, 'badgesByUsername']);
    Route::get('/profile/{username}/friends', [UserProfileController::class, 'friendsByUsername']);

    // Protected routes
    Route::group(["middleware" => "auth:api"], function () {
        // Auth routes
        Route::group(["prefix" => "auth"], function () {
            Route::post("logout", [AuthController::class, "logout"]);
            Route::post("refresh", [AuthController::class, "refresh"]);
            Route::get("me", [AuthController::class, "me"]);
        });
        
        // Email verification routes
        Route::post("email/verification-notification", [AuthController::class, "sendVerificationEmail"]);
        Route::post("email/verify", [AuthController::class, "verifyEmail"]);

        // User Profile routes (protected parts)
        Route::group(["prefix" => "profile"], function () {
            Route::get('/achievements', [UserProfileController::class, 'userAchievements']);
            Route::get('/badges', [UserProfileController::class, 'userBadges']);
            Route::get("/", [UserProfileController::class, "profile"]);
            Route::put("/", [UserProfileController::class, "update"]);
            Route::post("/avatar", [UserProfileController::class, "updateAvatar"]);
            Route::put("/password", [
                UserProfileController::class,
                "updatePassword",
            ]);
            Route::get("/email-verification-status", [UserProfileController::class, "emailVerificationStatus"]);
            Route::put("/notifications", [
                UserProfileController::class,
                "updateNotificationPreferences",
            ]);
            Route::put("/privacy", [
                UserProfileController::class,
                "updatePrivacySettings",
            ]);
            Route::get("/dashboard", [UserProfileController::class, "dashboard"]);
            Route::get("/notifications", [
                UserProfileController::class,
                "notifications",
            ]);
            Route::put("/notifications/{id}", [
                UserProfileController::class,
                "markNotificationAsRead",
            ]);
            // New route for updating company name specifically
            Route::put("/company", [
                UserProfileController::class,
                "updateCompanyName",
            ]);
            Route::post("/banner", [
                UserProfileController::class,
                "updateBanner",
            ]);
            Route::post('/block/{username}', [UserProfileController::class, 'blockUser']);
            Route::delete('/block/{username}', [UserProfileController::class, 'unblockUser']);
            Route::get('/friends/status/{username}', [UserProfileController::class, 'friendshipStatus']);
        });

        // Friendship routes (protected)
        Route::group(["prefix" => "friends"], function () {
            // Get friends list
            Route::get("/", [FriendshipController::class, "index"]);

            // Get friends' activity feed
            Route::get("/activity", [FriendshipController::class, "friendsActivity"]);

            // Get pending requests
            Route::get("/requests", [
                FriendshipController::class,
                "pendingRequests",
            ]);

            // Get sent requests
            Route::get("/sent-requests", [
                FriendshipController::class,
                "sentRequests",
            ]);

            // Send friend request
            Route::post("/request", [FriendshipController::class, "sendRequest"]);

            // Accept friend request
            Route::put("/accept/{friendshipId}", [
                FriendshipController::class,
                "acceptRequest",
            ]);

            // Decline friend request
            Route::put("/decline/{friendshipId}", [
                FriendshipController::class,
                "declineRequest",
            ]);

            // Remove friend
            Route::delete("/{friendshipId}", [
                FriendshipController::class,
                "removeFriend",
            ]);

            // Block user
            Route::post("/block", [FriendshipController::class, "blockUser"]);

            // Unblock user
            Route::put("/unblock/{friendshipId}", [
                FriendshipController::class,
                "unblockUser",
            ]);

            // Get blocked users
            Route::get("/blocked", [FriendshipController::class, "blockedUsers"]);

            // Search users to add as friends
            Route::get("/search", [FriendshipController::class, "searchUsers"]);

            // Cancel a sent friend request
            Route::delete("/request/{friendshipId}", [FriendshipController::class, "cancelRequest"]);
        });

        // Developer dashboard route (protected)
        Route::get("/developer/dashboard", [
            DeveloperDashboardController::class,
            "dashboard",
        ]);

        // Other protected routes will go here

        // Player/user notification routes (move inside v1 prefix group)
        Route::middleware('auth:api')->group(function () {
            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
            Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']);
        });

        Route::get('/user/profile', [\App\Http\Controllers\API\UserProfileController::class, 'profile']);

        // User billing/payment methods
        Route::prefix('billing')->group(function () {
            Route::post('/setup-intent', [\App\Http\Controllers\API\UserBillingController::class, 'createSetupIntent']);
            Route::post('/save-payment-method', [\App\Http\Controllers\API\UserBillingController::class, 'savePaymentMethod']);
            Route::get('/payment-method', [\App\Http\Controllers\API\UserBillingController::class, 'getPaymentMethod']);
            Route::delete('/payment-method', [\App\Http\Controllers\API\UserBillingController::class, 'deletePaymentMethod']);
        });
    });

    // Game management routes

    // Public game routes
    Route::group(["prefix" => "games"], function () {
        // Browse games
        Route::get("/", [GameController::class, "index"]);

        // Featured games
        Route::get("/featured", [GameController::class, "featured"]);

        // Newest games
        Route::get("/newest", [GameController::class, "newest"]);

        // Most played games
        Route::get("/most-played", [GameController::class, "mostPlayed"]);

        // Top rated games
        Route::get("/top-rated", [GameController::class, "topRated"]);

        // Game categories
        Route::get("/categories", [GameController::class, "categories"]);

        // Game tags
        Route::get("/tags", [GameController::class, "tags"]);

        // Game details - Changed from {slug} to {gameId}
        Route::get("/{gameId}", [GameController::class, "show"]);

        // Favorite/unfavorite game (authenticated)
        Route::middleware("auth:api")->group(function () {
            Route::post("/{gameId}/favorite", [GameController::class, "favorite"]);
            Route::delete("/{gameId}/favorite", [
                GameController::class,
                "unfavorite",
            ]);
            Route::get("/favorites", [GameController::class, "userFavorites"]);
            Route::get("/{gameId}/stats", [GameController::class, "userGameStats"]);
        });

        // Game reviews
        Route::group(["prefix" => "{gameId}/reviews"], function () {
            // Get reviews for a game
            Route::get("/", [GameReviewController::class, "index"]);

            // Authenticated review routes
            Route::middleware("auth:api")->group(function () {
                // Submit a review
                Route::post("/", [GameReviewController::class, "store"]);

                // Update a review
                Route::put("/{reviewId}", [GameReviewController::class, "update"]);

                // Delete a review
                Route::delete("/{reviewId}", [
                    GameReviewController::class,
                    "destroy",
                ]);

                // Vote on a review
                Route::post("/{reviewId}/vote", [
                    GameReviewController::class,
                    "vote",
                ]);

                // Get review comments
                Route::get("/{reviewId}/comments", [
                    GameReviewController::class,
                    "comments",
                ]);

                // Add a comment to a review
                Route::post("/{reviewId}/comments", [
                    GameReviewController::class,
                    "addComment",
                ]);

                // Delete a comment
                Route::delete("/comments/{commentId}", [
                    GameReviewController::class,
                    "deleteComment",
                ]);
            });
        });


    });

    Route::get("/developer/games/{gameId}/metrics", [
        DeveloperGameMetricsController::class,
        "getMetrics",
    ]);


    // Developer routes (must be authenticated as a developer)
    Route::middleware(["auth:api"])->group(function () {
        // Developer appearance settings
        Route::put("/developer/appearance", [UserProfileController::class, "updateAppearance"]);
        
        Route::group(["prefix" => "developer/games"], function () {
            // Get developer's games
            Route::get("/", [DeveloperGameController::class, "index"]);

            // Create a new game
            Route::post("/", [DeveloperGameController::class, "store"]);

            // Get detailed game info
            Route::get("/{gameId}", [DeveloperGameController::class, "show"]);

            // Update a game
            Route::put("/{gameId}", [DeveloperGameController::class, "update"]);

            // Upload game assets
            Route::post("/{gameId}/assets", [
                DeveloperGameController::class,
                "uploadAssets",
            ]);

            Route::get("/{gameId}/assets", [
                DeveloperGameController::class,
                "listAssets",
            ]);

            // ADD HERE:
            Route::post("{gameId}/assets/{assetId}/prepare-test", [
                \App\Http\Controllers\API\GameTestController::class,
                "prepareTest",
            ]);
            Route::post("{gameId}/assets/{assetId}/compatibility-report", [
                \App\Http\Controllers\API\GameTestController::class,
                "compatibilityReport",
            ]);

            // Upload screenshots
            Route::post("/{gameId}/screenshots", [
                DeveloperGameController::class,
                "uploadScreenshots",
            ]);

            // Delete a screenshot
            Route::delete("/{gameId}/screenshots/{screenshotId}", [
                DeveloperGameController::class,
                "deleteScreenshot",
            ]);

            // Publish a game
            Route::post("/{gameId}/publish", [
                DeveloperGameController::class,
                "publish",
            ]);

            // Unpublish a game
            Route::post("/{gameId}/unpublish", [
                DeveloperGameController::class,
                "unpublish",
            ]);

            // Game achievements management
            Route::group(["prefix" => "{gameId}/achievements"], function () {
                // Get achievements
                Route::get("/", [
                    DeveloperGameAchievementController::class,
                    "index",
                ]);

                // Create an achievement
                Route::post("/", [
                    DeveloperGameAchievementController::class,
                    "store",
                ]);

                // Update an achievement
                Route::put("/{achievementId}", [
                    DeveloperGameAchievementController::class,
                    "update",
                ]);

                // Delete an achievement
                Route::delete("/{achievementId}", [
                    DeveloperGameAchievementController::class,
                    "destroy",
                ]);
            });

            // Game leaderboard management
            Route::group(["prefix" => "{gameId}/leaderboards"], function () {
                // Get leaderboards
                Route::get("/", [
                    DeveloperGameLeaderboardController::class,
                    "index",
                ]);

                // Create a leaderboard
                Route::post("/", [
                    DeveloperGameLeaderboardController::class,
                    "store",
                ]);

                // Update a leaderboard
                Route::put("/{leaderboardId}", [
                    DeveloperGameLeaderboardController::class,
                    "update",
                ]);

                // Delete a leaderboard
                Route::delete("/{leaderboardId}", [
                    DeveloperGameLeaderboardController::class,
                    "destroy",
                ]);

                // Get leaderboard entries
                Route::get("/{leaderboardId}/entries", [
                    DeveloperGameLeaderboardController::class,
                    "entries",
                ]);
            });

            // Game analytics
            Route::get("/{gameId}/analytics", [
                DeveloperGameAnalyticsController::class,
                "index",
            ]);
            Route::get("/{gameId}/analytics/users", [
                DeveloperGameAnalyticsController::class,
                "users",
            ]);
            Route::get("/{gameId}/analytics/revenue", [
                DeveloperGameAnalyticsController::class,
                "revenue",
            ]);
            Route::get("/{gameId}/analytics/playtime", [
                DeveloperGameAnalyticsController::class,
                "playtime",
            ]);
        });
    });

    // Admin Game Management Routes
    Route::middleware(["auth:api", "role:admin"])->group(function () {
        Route::group(["prefix" => "admin/games"], function () {
            // Get all games (including unpublished)
            Route::get("/", [AdminGameController::class, "index"]);

            // Get games pending approval
            Route::get("/pending", [AdminGameController::class, "pendingApproval"]);

            // Approve a game
            Route::post("/{gameId}/approve", [
                AdminGameController::class,
                "approve",
            ]);

            // Reject a game
            Route::post("/{gameId}/reject", [AdminGameController::class, "reject"]);

            // Feature a game
            Route::post("/{gameId}/feature", [
                AdminGameController::class,
                "feature",
            ]);

            // Unfeature a game
            Route::post("/{gameId}/unfeature", [
                AdminGameController::class,
                "unfeature",
            ]);

            // Get featured games (admin)
            Route::get("/featured", [AdminGameController::class, "featured"]);

            // Delete a game
            Route::delete("/{gameId}", [AdminGameController::class, "destroy"]);

            // Manage game categories
            Route::resource("categories", AdminGameCategoryController::class);

            // Manage game tags
            Route::resource("tags", AdminGameTagController::class);
        });

        // Admin user management endpoints
        Route::get('/admin/users', [AdminUserController::class, 'index']);
        Route::get('/admin/users/banned', [AdminUserController::class, 'banned']);
        Route::get('/admin/users/{id}', [AdminUserController::class, 'show']);
        Route::post('/admin/users/{id}/ban', [AdminUserController::class, 'ban']);
        Route::post('/admin/users/{id}/unban', [AdminUserController::class, 'unban']);
        // Admin stats endpoints
        Route::get('/admin/stats/quick', [AdminStatsController::class, 'quick']);
        Route::get('/admin/stats/platform', [AdminStatsController::class, 'platform']);
    });

    Route::any("/test-csrf", function () {
        return response()->json(["ok" => true]);
    });

    // Game testing routes
    Route::middleware(["auth:api"])->group(function () {
        Route::group(["prefix" => "developer/games"], function () {
            // Prepare a game for testing
            Route::get("/{gameId}/assets/{assetId}/test", [
                GameTestController::class,
                "prepareTest",
            ]);
            // Submit compatibility report
            Route::post("/{gameId}/assets/{assetId}/test-report", [
                GameTestController::class,
                "compatibilityReport",
            ]);
        });
    });

    // 2FA endpoints
    Route::middleware('auth:api')->prefix('profile/2fa')->group(function () {
        Route::post('/generate', [\App\Http\Controllers\API\TwoFactorAuthController::class, 'generateSecret']);
        Route::post('/enable', [\App\Http\Controllers\API\TwoFactorAuthController::class, 'enable']);
        Route::post('/disable', [\App\Http\Controllers\API\TwoFactorAuthController::class, 'disable']);
    });

    // Developer billing/payment methods
Route::middleware('auth:api')->prefix('developer')->group(function () {
    Route::post('/billing/setup-intent', [\App\Http\Controllers\API\DeveloperBillingController::class, 'createSetupIntent']);
    Route::post('/billing/save-payment-method', [\App\Http\Controllers\API\DeveloperBillingController::class, 'savePaymentMethod']);
    Route::get('/billing/payment-method', [\App\Http\Controllers\API\DeveloperBillingController::class, 'getPaymentMethod']);
    Route::delete('/billing/payment-method', [\App\Http\Controllers\API\DeveloperBillingController::class, 'deletePaymentMethod']);
});
    Route::get('/billing/stripe-publishable-key', function () {
        return response()->json(['key' => config('services.stripe.key')]);
    });

    // Platform Updates (News/Announcements)
    Route::get('/platform-updates', [PlatformUpdateController::class, 'index']);
    Route::middleware(['auth:api', 'role:admin'])->group(function () {
        Route::post('/platform-updates', [PlatformUpdateController::class, 'store']);
        Route::put('/platform-updates/{id}', [PlatformUpdateController::class, 'update']);
        Route::delete('/platform-updates/{id}', [PlatformUpdateController::class, 'destroy']);
    });

    // --- SEARCH ENDPOINT ---
    Route::get('/search', [SearchController::class, 'search']);
});

// Admin: Newsletter system
Route::prefix('v1')->middleware(['auth:api', 'role:admin'])->group(function () {
    Route::post('/admin/newsletter', [NewsletterController::class, 'send']);
});

// --- COMMUNITY: GROUPS & FORUMS ---
Route::prefix('v1')->middleware('auth:api')->group(function () {
    // GROUPS
    Route::prefix('groups')->group(function () {
        Route::get('/', [\App\Http\Controllers\API\GroupController::class, 'index']); // List/search groups
        Route::get('/recommended', [\App\Http\Controllers\API\GroupController::class, 'recommended']); // Recommended groups
        Route::get('/{group}', [\App\Http\Controllers\API\GroupController::class, 'show']); // Group details
        Route::post('/', [\App\Http\Controllers\API\GroupController::class, 'store']); // Create group
        Route::post('/{group}/join', [\App\Http\Controllers\API\GroupController::class, 'join']); // Join group
        Route::post('/{group}/leave', [\App\Http\Controllers\API\GroupController::class, 'leave']); // Leave group
        Route::post('/{group}/kick', [\App\Http\Controllers\API\GroupController::class, 'kick']);
        Route::post('/{group}/ban', [\App\Http\Controllers\API\GroupController::class, 'ban']);
        Route::post('/{group}/promote', [\App\Http\Controllers\API\GroupController::class, 'promote']);
        Route::post('/{group}/demote', [\App\Http\Controllers\API\GroupController::class, 'demote']);
        Route::delete('/{group}', [\App\Http\Controllers\API\GroupController::class, 'destroy']);
        Route::get('/{group}/members', [\App\Http\Controllers\API\GroupController::class, 'members']); // List group members
        Route::get('/{group}/is-member', [\App\Http\Controllers\API\GroupController::class, 'isMember']); // <-- Add this line
        // Group posts
        Route::get('/{group}/posts', [\App\Http\Controllers\API\GroupPostController::class, 'index']);
        Route::post('/{group}/posts', [\App\Http\Controllers\API\GroupPostController::class, 'store']);
        Route::post('/posts/{post}/like', [\App\Http\Controllers\API\GroupPostController::class, 'like']);
        Route::post('/posts/{post}/unlike', [\App\Http\Controllers\API\GroupPostController::class, 'unlike']);
        Route::post('/posts/{post}/comment', [\App\Http\Controllers\API\GroupPostController::class, 'comment']);
        Route::delete('/posts/{post}/comment/{comment}', [\App\Http\Controllers\API\GroupPostController::class, 'deleteComment']);
    });
    // FORUMS
    Route::prefix('forums')->group(function () {
        Route::get('/', [\App\Http\Controllers\API\ForumController::class, 'index']); // List forums
        Route::get('/{forum}', [\App\Http\Controllers\API\ForumController::class, 'show']); // Forum details
        // Threads
        Route::get('/{forum}/threads', [\App\Http\Controllers\API\ForumThreadController::class, 'index']);
        Route::post('/{forum}/threads', [\App\Http\Controllers\API\ForumThreadController::class, 'store']);
        Route::get('/threads/{thread}', [\App\Http\Controllers\API\ForumThreadController::class, 'show']);
        // Posts (replies)
        Route::post('/threads/{thread}/reply', [\App\Http\Controllers\API\ForumPostController::class, 'reply']);
        Route::post('/posts/{post}/upvote', [\App\Http\Controllers\API\ForumPostController::class, 'upvote']);
        Route::post('/posts/{post}/downvote', [\App\Http\Controllers\API\ForumPostController::class, 'downvote']);
        Route::delete('/posts/{post}', [\App\Http\Controllers\API\ForumPostController::class, 'destroy']);
        Route::post('/posts/{post}/reply', [\App\Http\Controllers\API\ForumPostController::class, 'replyToReply']);
    });
});

// --- CHAT ROOMS (Discord-like chat) ---
Route::prefix('v1')->middleware('auth:api')->group(function () {
    Route::prefix('chat-rooms')->group(function () {
        Route::get('/', [\App\Http\Controllers\API\ChatRoomController::class, 'index']); // List user chat rooms
        Route::post('/', [\App\Http\Controllers\API\ChatRoomController::class, 'store']); // Create chat room
        Route::get('/{room}', [\App\Http\Controllers\API\ChatRoomController::class, 'show']); // Get chat room details
        Route::post('/{room}/members', [\App\Http\Controllers\API\ChatRoomController::class, 'addMembers']); // Add members
        Route::delete('/{room}/members/{user}', [\App\Http\Controllers\API\ChatRoomController::class, 'removeMember']); // Remove member
        Route::get('/{room}/messages', [\App\Http\Controllers\API\ChatRoomController::class, 'messages']); // Fetch messages
        Route::post('/{room}/messages', [\App\Http\Controllers\API\ChatRoomController::class, 'sendMessage']); // Send message
        Route::patch('/{room}', [\App\Http\Controllers\API\ChatRoomController::class, 'update']); // Update chat room info (name, picture)
        Route::post('/{room}/picture', [\App\Http\Controllers\API\ChatRoomController::class, 'updatePicture']); // Upload group picture
    });
});

// --- VOICE CALLS (Discord-like voice chat) ---
Route::prefix('v1')->middleware('auth:api')->group(function () {
    Route::prefix('voice-calls')->group(function () {
        Route::post('/start', [\App\Http\Controllers\API\VoiceCallController::class, 'start']);
        Route::post('/{call}/join', [\App\Http\Controllers\API\VoiceCallController::class, 'join']);
        Route::post('/{call}/leave', [\App\Http\Controllers\API\VoiceCallController::class, 'leave']);
        Route::post('/{call}/end', [\App\Http\Controllers\API\VoiceCallController::class, 'end']);
        Route::get('/{call}/participants', [\App\Http\Controllers\API\VoiceCallController::class, 'participants']);
    });
});

// Legacy routes outside the v1 prefix (for backward compatibility)
// Developer dashboard route for backward compatibility
Route::middleware(["auth:api"])->group(function () {
    Route::get("/developer/dashboard", [
        DeveloperDashboardController::class,
        "dashboard",
    ]);
});

// User routes for developer followers (protected)
Route::middleware('auth:api')->prefix('user')->group(function () {
    Route::post('/follow-developer/{developerId}', [DeveloperFollowerController::class, 'follow']);
    Route::delete('/unfollow-developer/{developerId}', [DeveloperFollowerController::class, 'unfollow']);
    Route::put('/developer-preferences/{developerId}', [DeveloperFollowerController::class, 'updatePreferences']);
    Route::get('/followed-developers', [DeveloperFollowerController::class, 'getFollowedDevelopers']);
});

// Developer notification routes (protected)
Route::middleware('auth:api')->prefix('developer')->group(function () {
    // Specific routes first
    Route::get('/notifications/unread-count', [DeveloperNotificationController::class, 'unreadCount']);
    // Then general routes
    Route::get('/notifications', [DeveloperNotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [DeveloperNotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [DeveloperNotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [DeveloperNotificationController::class, 'destroy']);
    Route::get('/followers', [DeveloperFollowerController::class, 'getFollowers']);
});
