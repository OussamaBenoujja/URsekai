<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Developer;
use App\Models\User;
use App\Models\Notification;
use App\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminGameController extends Controller
{
    use ApiResponser;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:admin');
    }

    /**
     * Get all games with advanced filtering for admin.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Game::query();

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'published':
                    $query->where('is_published', true)
                          ->where('is_approved', true);
                    break;
                case 'pending':
                    $query->where('is_published', true)
                          ->where('is_approved', false);
                    break;
                case 'draft':
                    $query->where('is_published', false);
                    break;
                case 'featured':
                    $query->where('is_featured', true);
                    break;
            }
        }

        // Filter by developer
        if ($request->has('developer_id')) {
            $query->where('developer_id', $request->developer_id);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('main_category_id', $request->category_id);
        }

        // Filter by age rating
        if ($request->has('age_rating')) {
            $query->where('age_rating', $request->age_rating);
        }

        // Search by title
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Filter by release date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('release_date', [$request->from_date, $request->to_date]);
        }

        // Sort options
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        // Validate sort field
        $allowedSortFields = [
            'title', 'release_date', 'created_at', 'updated_at', 
            'total_plays', 'average_rating'
        ];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        // Include relationships
        $query->with(['developer.user', 'mainCategory', 'screenshots' => function($q) {
            $q->active()->ordered()->limit(1);
        }]);

        // Paginate results
        $games = $query->paginate($request->input('per_page', 20));

        return $this->success($games);
    }

    /**
     * Get games pending approval.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pendingApproval(Request $request)
    {
        $query = Game::where('is_published', true)
                     ->where('is_approved', false);

        // Include relationships
        $query->with(['developer.user', 'mainCategory', 'screenshots' => function($q) {
            $q->active()->ordered()->limit(1);
        }]);

        // Sort by most recent first
        $query->orderBy('updated_at', 'desc');

        // Paginate results
        $games = $query->paginate($request->input('per_page', 20));

        return $this->success($games);
    }

    /**
     * Get detailed information about a specific game.
     *
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($gameId)
    {
        $game = Game::with([
                'developer.user',
                'mainCategory',
                'categories',
                'tags',
                'screenshots' => function($q) {
                    $q->active()->ordered();
                },
                'videos',
                'assets',
                'reviews' => function($q) {
                    $q->orderBy('created_at', 'desc')->limit(5);
                },
                'achievements',
                'leaderboards'
            ])
            ->findOrFail($gameId);

        return $this->success($game);
    }

    /**
     * Approve a game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, $gameId)
    {
        $game = Game::findOrFail($gameId);
        
        // Skip if already approved
        if ($game->is_approved) {
            return $this->error('Game is already approved', 422);
        }
        
        // Make sure game is published
        if (!$game->is_published) {
            return $this->error('Cannot approve unpublished game', 422);
        }
        
        // Update game status
        $game->is_approved = true;
        $game->approval_date = now();
        $game->approved_by = Auth::id();
        $game->save();
        
        // Get developer to notify
        $developer = Developer::with('user')->find($game->developer_id);
        
        if ($developer && $developer->user) {
            // Create notification for developer
            Notification::create([
                'user_id' => $developer->user->user_id,
                'type' => 'game_approved',
                'title' => 'Game Approved',
                'message' => "Your game '{$game->title}' has been approved and is now live on the platform.",
                'is_read' => false,
                'created_at' => now(),
                'priority' => 'high',
                'link' => "/developer/games/{$game->game_id}",
                'related_id' => $game->game_id,
                'related_type' => 'game'
            ]);
        }

        return $this->success($game, 'Game approved successfully');
    }

    /**
     * Reject a game with reason.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, $gameId)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $game = Game::findOrFail($gameId);
        
        // Skip if already rejected (not approved and has rejection reason)
        if (!$game->is_approved && $game->rejection_reason) {
            return $this->error('Game is already rejected', 422);
        }
        
        // Update game status
        $game->is_approved = false;
        $game->rejection_reason = $request->rejection_reason;
        $game->approved_by = Auth::id(); // Track who rejected it
        $game->save();
        
        // Get developer to notify
        $developer = Developer::with('user')->find($game->developer_id);
        
        if ($developer && $developer->user) {
            // Create notification for developer
            Notification::create([
                'user_id' => $developer->user->user_id,
                'type' => 'game_rejected',
                'title' => 'Game Rejected',
                'message' => "Your game '{$game->title}' has been rejected. Please review the feedback and make necessary changes.",
                'is_read' => false,
                'created_at' => now(),
                'priority' => 'high',
                'link' => "/developer/games/{$game->game_id}",
                'related_id' => $game->game_id,
                'related_type' => 'game'
            ]);
        }

        return $this->success($game, 'Game rejected successfully');
    }

    /**
     * Feature a game.
     *
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function feature($gameId)
    {
        $game = Game::findOrFail($gameId);
        
        // Skip if already featured
        if ($game->is_featured) {
            return $this->error('Game is already featured', 422);
        }
        
        // Make sure game is published and approved
        if (!$game->is_published || !$game->is_approved) {
            return $this->error('Only published and approved games can be featured', 422);
        }
        
        // Update game status
        $game->is_featured = true;
        $game->save();
        
        // Get developer to notify
        $developer = Developer::with('user')->find($game->developer_id);
        
        if ($developer && $developer->user) {
            // Create notification for developer
            Notification::create([
                'user_id' => $developer->user->user_id,
                'type' => 'game_featured',
                'title' => 'Game Featured',
                'message' => "Congratulations! Your game '{$game->title}' is now featured on the platform.",
                'is_read' => false,
                'created_at' => now(),
                'priority' => 'normal',
                'link' => "/developer/games/{$game->game_id}",
                'related_id' => $game->game_id,
                'related_type' => 'game'
            ]);
        }

        return $this->success($game, 'Game featured successfully');
    }

    /**
     * Unfeature a game.
     *
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unfeature($gameId)
    {
        $game = Game::findOrFail($gameId);
        
        // Skip if not featured
        if (!$game->is_featured) {
            return $this->error('Game is not featured', 422);
        }
        
        // Update game status
        $game->is_featured = false;
        $game->save();

        return $this->success($game, 'Game unfeatured successfully');
    }

    /**
     * Delete a game.
     *
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($gameId)
    {
        $game = Game::findOrFail($gameId);
        
        // Soft delete the game
        $game->deleted_at = now();
        $game->is_published = false;
        $game->is_featured = false;
        $game->save();
        
        // Get developer to notify
        $developer = Developer::with('user')->find($game->developer_id);
        
        if ($developer && $developer->user) {
            // Create notification for developer
            Notification::create([
                'user_id' => $developer->user->user_id,
                'type' => 'game_deleted',
                'title' => 'Game Deleted',
                'message' => "Your game '{$game->title}' has been removed from the platform by an administrator.",
                'is_read' => false,
                'created_at' => now(),
                'priority' => 'high',
                'related_type' => 'game'
            ]);
        }

        return $this->success(null, 'Game deleted successfully');
    }

    /**
     * Update a game (admin override).
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $gameId)
    {
        $game = Game::findOrFail($gameId);
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:100',
            'description' => 'sometimes|string',
            'short_description' => 'sometimes|string|max:255',
            'main_category_id' => 'sometimes|exists:game_categories,category_id',
            'age_rating' => 'sometimes|in:E,E10+,T,M,A',
            'is_published' => 'sometimes|boolean',
            'is_approved' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'monetization_type' => 'sometimes|in:free,premium,freemium,ads,subscription',
            'price' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'supports_fullscreen' => 'sometimes|boolean',
            'supports_mobile' => 'sometimes|boolean',
            'has_multiplayer' => 'sometimes|boolean',
            'max_players' => 'sometimes|integer|min:1',
            'custom_css' => 'sometimes|string',
            'custom_javascript' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Update game fields
        foreach ($validator->validated() as $key => $value) {
            $game->{$key} = $value;
        }
        
        // Update slug if title changes
        if ($request->has('title') && $game->title !== $request->title) {
            // Create slug from title
            $slug = Str::slug($request->title);
            $baseSlug = $slug;
            $counter = 1;
            
            // Make sure slug is unique
            while (Game::where('slug', $slug)->where('game_id', '!=', $gameId)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $game->slug = $slug;
        }
        
        $game->updated_at = now();
        $game->save();
        
        // If game approval status changed, notify developer
        if ($request->has('is_approved') && $game->getOriginal('is_approved') !== $request->is_approved) {
            $developer = Developer::with('user')->find($game->developer_id);
            
            if ($developer && $developer->user) {
                if ($request->is_approved) {
                    // Create approval notification
                    Notification::create([
                        'user_id' => $developer->user->user_id,
                        'type' => 'game_approved',
                        'title' => 'Game Approved',
                        'message' => "Your game '{$game->title}' has been approved by an administrator.",
                        'is_read' => false,
                        'created_at' => now(),
                        'priority' => 'high',
                        'link' => "/developer/games/{$game->game_id}",
                        'related_id' => $game->game_id,
                        'related_type' => 'game'
                    ]);
                } else {
                    // Create rejection notification
                    Notification::create([
                        'user_id' => $developer->user->user_id,
                        'type' => 'game_status_changed',
                        'title' => 'Game Status Changed',
                        'message' => "Your game '{$game->title}' approval status has been changed by an administrator.",
                        'is_read' => false,
                        'created_at' => now(),
                        'priority' => 'high',
                        'link' => "/developer/games/{$game->game_id}",
                        'related_id' => $game->game_id,
                        'related_type' => 'game'
                    ]);
                }
            }
        }

        return $this->success($game, 'Game updated successfully');
    }
}