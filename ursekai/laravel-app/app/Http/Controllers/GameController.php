<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameCategory;
use App\Models\GameTag;
use App\Models\GameFavorite;
use App\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GameController extends Controller
{
    use ApiResponser;

    /**
     * Get a list of games with filtering and pagination.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Game::published();

        // Filter by category
        if ($request->has('category_id')) {
            $query->inCategory($request->category_id);
        }

        // Filter by tag
        if ($request->has('tag_id')) {
            $query->withTag($request->tag_id);
        }

        // Filter by tags (array)
        if ($request->has('tags') && is_array($request->tags)) {
            $query->withTags($request->tags);
        }

        // Filter by price range
        if ($request->has('max_price')) {
            $query->maxPrice($request->max_price);
        }

        // Filter by free games
        if ($request->boolean('free')) {
            $query->free();
        }

        // Search by title or description
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by features
        if ($request->boolean('multiplayer')) {
            $query->where('has_multiplayer', true);
        }

        if ($request->boolean('achievements')) {
            $query->where('has_achievements', true);
        }

        if ($request->boolean('leaderboard')) {
            $query->where('has_leaderboard', true);
        }

        // Filter by age rating
        if ($request->has('age_rating')) {
            $query->where('age_rating', $request->age_rating);
        }

        // Filter by mobile support
        if ($request->has('mobile')) {
            $query->where('supports_mobile', $request->boolean('mobile'));
        }

        // Sort results
        $sortField = $request->input('sort', 'release_date');
        $sortDirection = $request->input('direction', 'desc');

        // Validate sort field
        $allowedSortFields = [
            'release_date', 'title', 'average_rating', 
            'total_plays', 'total_ratings'
        ];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'release_date';
        }

        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        // Include relationships
        $query->with(['mainCategory', 'developer', 'screenshots' => function($q) {
            $q->active()->ordered()->limit(1);
        }]);

        // Paginate results
        $perPage = $request->input('per_page', 20);
        $games = $query->paginate($perPage);

        return $this->success($games);
    }

    /**
     * Get featured games.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function featured(Request $request)
    {
        $query = Game::featured();

        // Include relationships
        $query->with(['mainCategory', 'developer', 'screenshots' => function($q) {
            $q->active()->ordered()->limit(1);
        }]);

        // Get featured games
        $limit = $request->input('limit', 10);
        $games = $query->take($limit)->get();

        return $this->success($games);
    }

    /**
     * Get newest games.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newest(Request $request)
    {
        $query = Game::published()->orderBy('release_date', 'desc');

        // Include relationships
        $query->with(['mainCategory', 'developer', 'screenshots' => function($q) {
            $q->active()->ordered()->limit(1);
        }]);

        // Get newest games
        $limit = $request->input('limit', 10);
        $games = $query->take($limit)->get();

        return $this->success($games);
    }

    /**
     * Get most played games.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mostPlayed(Request $request)
    {
        $query = Game::published()->orderBy('total_plays', 'desc');

        // Include relationships
        $query->with(['mainCategory', 'developer', 'screenshots' => function($q) {
            $q->active()->ordered()->limit(1);
        }]);

        // Get most played games
        $limit = $request->input('limit', 10);
        $games = $query->take($limit)->get();

        return $this->success($games);
    }

    /**
     * Get top rated games.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function topRated(Request $request)
    {
        $query = Game::published()
                    ->where('average_rating', '>=', 4.0)
                    ->where('total_ratings', '>=', 10)
                    ->orderBy('average_rating', 'desc');

        // Include relationships
        $query->with(['mainCategory', 'developer', 'screenshots' => function($q) {
            $q->active()->ordered()->limit(1);
        }]);

        // Get top rated games
        $limit = $request->input('limit', 10);
        $games = $query->take($limit)->get();

        return $this->success($games);
    }

    /**
     * Get game categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories()
    {
        $categories = GameCategory::active()->ordered()->get();
        return $this->success($categories);
    }

    /**
     * Get popular tags.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tags(Request $request)
    {
        $tags = GameTag::active()
                    ->withCount('games')
                    ->orderBy('games_count', 'desc')
                    ->take($request->input('limit', 20))
                    ->get();

        return $this->success($tags);
    }

    /**
     * Get detailed information about a specific game.
     *
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $slug)
    {
        $game = Game::where('slug', $slug)
                    ->with([
                        'developer',
                        'mainCategory',
                        'categories',
                        'tags',
                        'screenshots' => function($q) {
                            $q->active()->ordered();
                        },
                        'videos' => function($q) {
                            $q->where('is_active', true)->orderBy('display_order', 'asc');
                        },
                        'achievements' => function($q) {
                            $q->where('is_active', true)->where('is_hidden', false);
                        },
                        'leaderboards' => function($q) {
                            $q->where('is_active', true)->where('is_global', true);
                        }
                    ])
                    ->firstOrFail();

        // Check if game is published (non-admins can only see published games)
        if (!$game->is_published && (!Auth::check() || (Auth::check() && Auth::user()->role !== 'admin'))) {
            return $this->error('Game not found', 404);
        }

        // If user is authenticated, check if they've favorited the game
        if (Auth::check()) {
            $isFavorited = GameFavorite::where('user_id', Auth::id())
                                    ->where('game_id', $game->game_id)
                                    ->exists();
            $game->is_favorited = $isFavorited;
        }

        // Get similar games (same category, same tags)
        $similarGames = Game::published()
                        ->where('game_id', '!=', $game->game_id)
                        ->where(function($query) use ($game) {
                            $query->where('main_category_id', $game->main_category_id)
                                ->orWhereHas('categories', function($q) use ($game) {
                                    $q->whereIn('category_id', $game->categories->pluck('category_id'));
                                });
                        })
                        ->with(['mainCategory', 'screenshots' => function($q) {
                            $q->active()->ordered()->limit(1);
                        }])
                        ->take(6)
                        ->get();

        $game->similar_games = $similarGames;

        // Increment view count
        // This could be tracked in a separate table instead of direct update
        // to avoid race conditions and enable more detailed analytics
        $game->increment('total_plays');

        return $this->success($game);
    }

    /**
     * Add a game to user's favorites.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function favorite(Request $request, $gameId)
    {
        if (!Auth::check()) {
            return $this->error('Authentication required', 401);
        }

        $game = Game::findOrFail($gameId);
        
        // Check if already favorited
        $exists = GameFavorite::where('user_id', Auth::id())
                            ->where('game_id', $gameId)
                            ->exists();

        if ($exists) {
            return $this->error('Game already in favorites', 422);
        }

        // Add to favorites
        GameFavorite::create([
            'user_id' => Auth::id(),
            'game_id' => $gameId,
            'created_at' => now()
        ]);

        return $this->success(null, 'Game added to favorites');
    }

    /**
     * Remove a game from user's favorites.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unfavorite(Request $request, $gameId)
    {
        if (!Auth::check()) {
            return $this->error('Authentication required', 401);
        }

        $favorite = GameFavorite::where('user_id', Auth::id())
                                ->where('game_id', $gameId)
                                ->first();

        if (!$favorite) {
            return $this->error('Game not in favorites', 422);
        }

        $favorite->delete();

        return $this->success(null, 'Game removed from favorites');
    }

    /**
     * Get user's favorited games.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userFavorites(Request $request)
    {
        if (!Auth::check()) {
            return $this->error('Authentication required', 401);
        }

        $favorites = GameFavorite::where('user_id', Auth::id())
                                ->with(['game' => function($q) {
                                    $q->with(['mainCategory', 'screenshots' => function($sq) {
                                        $sq->active()->ordered()->limit(1);
                                    }]);
                                }])
                                ->orderBy('created_at', 'desc')
                                ->paginate($request->input('per_page', 20));

        return $this->success($favorites);
    }

    /**
     * Get game playtime and progression for the authenticated user.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function userGameStats(Request $request, $gameId)
    {
        if (!Auth::check()) {
            return $this->error('Authentication required', 401);
        }

        $game = Game::findOrFail($gameId);
        
        // Get user's game progress
        $progress = \App\Models\UserGameProgress::where('user_id', Auth::id())
                                            ->where('game_id', $gameId)
                                            ->first();

        if (!$progress) {
            return $this->success([
                'has_played' => false,
                'total_time_played_minutes' => 0,
                'times_played' => 0,
                'first_played' => null,
                'last_played' => null,
                'achievements_unlocked' => 0,
                'total_achievements' => $game->achievements()->count(),
            ]);
        }

        // Get user's unlocked achievements for this game
        $achievementsUnlocked = \App\Models\UserAchievement::whereHas('achievement', function($q) use ($gameId) {
                                                        $q->where('game_id', $gameId);
                                                    })
                                                    ->where('user_id', Auth::id())
                                                    ->count();

        return $this->success([
            'has_played' => true,
            'total_time_played_minutes' => $progress->total_time_played_minutes,
            'times_played' => $progress->times_played,
            'first_played' => $progress->first_played,
            'last_played' => $progress->last_played,
            'achievements_unlocked' => $achievementsUnlocked,
            'total_achievements' => $game->achievements()->count(),
            'current_level' => $progress->current_level,
            'highest_level_reached' => $progress->highest_level_reached,
            'total_score' => $progress->total_score,
            'highest_score' => $progress->highest_score,
            'xp_earned' => $progress->xp_earned,
        ]);
    }
}