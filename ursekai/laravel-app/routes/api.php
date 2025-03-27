<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserProfileController;
use App\Http\Controllers\API\FriendshipController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\GameController;
use App\Http\Controllers\API\GameReviewController;
use App\Http\Controllers\API\DeveloperGameController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Protected routes
Route::group(['middleware' => 'auth:api'], function () {
    // Auth routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
    
    // User Profile routes
    Route::group(['prefix' => 'profile'], function () {
        Route::get('/', [UserProfileController::class, 'profile']);
        Route::put('/', [UserProfileController::class, 'update']);
        Route::post('/avatar', [UserProfileController::class, 'updateAvatar']);
        Route::put('/password', [UserProfileController::class, 'updatePassword']);
        Route::put('/notifications', [UserProfileController::class, 'updateNotificationPreferences']);
        Route::put('/privacy', [UserProfileController::class, 'updatePrivacySettings']);
        Route::get('/dashboard', [UserProfileController::class, 'dashboard']);
        Route::get('/notifications', [UserProfileController::class, 'notifications']);
        Route::put('/notifications/{id}', [UserProfileController::class, 'markNotificationAsRead']);
    });
    
    // Public user profiles
    Route::get('/users/{id}', [UserProfileController::class, 'show']);
    

    Route::group(['prefix' => 'friends'], function () {
        // Get friends list
        Route::get('/', [FriendshipController::class, 'index']);
        
        // Get pending requests
        Route::get('/requests', [FriendshipController::class, 'pendingRequests']);
        
        // Get sent requests
        Route::get('/sent-requests', [FriendshipController::class, 'sentRequests']);
        
        // Send friend request
        Route::post('/request', [FriendshipController::class, 'sendRequest']);
        
        // Accept friend request
        Route::put('/accept/{friendshipId}', [FriendshipController::class, 'acceptRequest']);
        
        // Decline friend request
        Route::put('/decline/{friendshipId}', [FriendshipController::class, 'declineRequest']);
        
        // Remove friend
        Route::delete('/{friendshipId}', [FriendshipController::class, 'removeFriend']);
        
        // Block user
        Route::post('/block', [FriendshipController::class, 'blockUser']);
        
        // Unblock user
        Route::put('/unblock/{friendshipId}', [FriendshipController::class, 'unblockUser']);
        
        // Get blocked users
        Route::get('/blocked', [FriendshipController::class, 'blockedUsers']);
        
        // Search users to add as friends
        Route::get('/search', [FriendshipController::class, 'searchUsers']);
    });


    // Other protected routes will go here
});


// Game management routes (to be added to your routes/api.php file)

// Public game routes
Route::group(['prefix' => 'games'], function () {
    // Browse games
    Route::get('/', [GameController::class, 'index']);
    
    // Featured games
    Route::get('/featured', [GameController::class, 'featured']);
    
    // Newest games
    Route::get('/newest', [GameController::class, 'newest']);
    
    // Most played games
    Route::get('/most-played', [GameController::class, 'mostPlayed']);
    
    // Top rated games
    Route::get('/top-rated', [GameController::class, 'topRated']);
    
    // Game categories
    Route::get('/categories', [GameController::class, 'categories']);
    
    // Game tags
    Route::get('/tags', [GameController::class, 'tags']);
    
    // Game details
    Route::get('/{slug}', [GameController::class, 'show']);
    
    // Favorite/unfavorite game (authenticated)
    Route::middleware('auth:api')->group(function () {
        Route::post('/{gameId}/favorite', [GameController::class, 'favorite']);
        Route::delete('/{gameId}/favorite', [GameController::class, 'unfavorite']);
        Route::get('/favorites', [GameController::class, 'userFavorites']);
        Route::get('/{gameId}/stats', [GameController::class, 'userGameStats']);
    });
    
    // Game reviews
    Route::group(['prefix' => '{gameId}/reviews'], function () {
        // Get reviews for a game
        Route::get('/', [GameReviewController::class, 'index']);
        
        // Authenticated review routes
        Route::middleware('auth:api')->group(function () {
            // Submit a review
            Route::post('/', [GameReviewController::class, 'store']);
            
            // Update a review
            Route::put('/{reviewId}', [GameReviewController::class, 'update']);
            
            // Delete a review
            Route::delete('/{reviewId}', [GameReviewController::class, 'destroy']);
            
            // Vote on a review
            Route::post('/{reviewId}/vote', [GameReviewController::class, 'vote']);
            
            // Get review comments
            Route::get('/{reviewId}/comments', [GameReviewController::class, 'comments']);
            
            // Add a comment to a review
            Route::post('/{reviewId}/comments', [GameReviewController::class, 'addComment']);
            
            // Delete a comment
            Route::delete('/comments/{commentId}', [GameReviewController::class, 'deleteComment']);
        });
    });
});

// Developer routes (must be authenticated as a developer)
Route::middleware(['auth:api'])->group(function () {
    Route::group(['prefix' => 'developer/games'], function () {
        // Get developer's games
        Route::get('/', [DeveloperGameController::class, 'index']);
        
        // Create a new game
        Route::post('/', [DeveloperGameController::class, 'store']);
        
        // Get detailed game info
        Route::get('/{gameId}', [DeveloperGameController::class, 'show']);
        
        // Update a game
        Route::put('/{gameId}', [DeveloperGameController::class, 'update']);
        
        // Upload game assets
        Route::post('/{gameId}/assets', [DeveloperGameController::class, 'uploadAssets']);
        
        // Upload screenshots
        Route::post('/{gameId}/screenshots', [DeveloperGameController::class, 'uploadScreenshots']);
        
        // Delete a screenshot
        Route::delete('/{gameId}/screenshots/{screenshotId}', [DeveloperGameController::class, 'deleteScreenshot']);
        
        // Publish a game
        Route::post('/{gameId}/publish', [DeveloperGameController::class, 'publish']);
        
        // Unpublish a game
        Route::post('/{gameId}/unpublish', [DeveloperGameController::class, 'unpublish']);
        
        // Game achievements management
        Route::group(['prefix' => '{gameId}/achievements'], function () {
            // Get achievements
            Route::get('/', [DeveloperGameAchievementController::class, 'index']);
            
            // Create an achievement
            Route::post('/', [DeveloperGameAchievementController::class, 'store']);
            
            // Update an achievement
            Route::put('/{achievementId}', [DeveloperGameAchievementController::class, 'update']);
            
            // Delete an achievement
            Route::delete('/{achievementId}', [DeveloperGameAchievementController::class, 'destroy']);
        });
        
        // Game leaderboard management
        Route::group(['prefix' => '{gameId}/leaderboards'], function () {
            // Get leaderboards
            Route::get('/', [DeveloperGameLeaderboardController::class, 'index']);
            
            // Create a leaderboard
            Route::post('/', [DeveloperGameLeaderboardController::class, 'store']);
            
            // Update a leaderboard
            Route::put('/{leaderboardId}', [DeveloperGameLeaderboardController::class, 'update']);
            
            // Delete a leaderboard
            Route::delete('/{leaderboardId}', [DeveloperGameLeaderboardController::class, 'destroy']);
            
            // Get leaderboard entries
            Route::get('/{leaderboardId}/entries', [DeveloperGameLeaderboardController::class, 'entries']);
        });
        
        // Game analytics
        Route::get('/{gameId}/analytics', [DeveloperGameAnalyticsController::class, 'index']);
        Route::get('/{gameId}/analytics/users', [DeveloperGameAnalyticsController::class, 'users']);
        Route::get('/{gameId}/analytics/revenue', [DeveloperGameAnalyticsController::class, 'revenue']);
        Route::get('/{gameId}/analytics/playtime', [DeveloperGameAnalyticsController::class, 'playtime']);
    });
});

// Admin Game Management Routes
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::group(['prefix' => 'admin/games'], function () {
        // Get all games (including unpublished)
        Route::get('/', [AdminGameController::class, 'index']);
        
        // Get games pending approval
        Route::get('/pending', [AdminGameController::class, 'pendingApproval']);
        
        // Approve a game
        Route::post('/{gameId}/approve', [AdminGameController::class, 'approve']);
        
        // Reject a game
        Route::post('/{gameId}/reject', [AdminGameController::class, 'reject']);
        
        // Feature a game
        Route::post('/{gameId}/feature', [AdminGameController::class, 'feature']);
        
        // Unfeature a game
        Route::post('/{gameId}/unfeature', [AdminGameController::class, 'unfeature']);
        
        // Delete a game
        Route::delete('/{gameId}', [AdminGameController::class, 'destroy']);
        
        // Manage game categories
        Route::resource('categories', AdminGameCategoryController::class);
        
        // Manage game tags
        Route::resource('tags', AdminGameTagController::class);
    });
});