<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Developer;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeveloperGameLeaderboardController extends Controller
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
    }

    /**
     * Get all leaderboards for a specific game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $gameId)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Ensure the game belongs to this developer
        $game = Game::where('game_id', $gameId)
                    ->where('developer_id', $developer->developer_id)
                    ->firstOrFail();

        $query = Leaderboard::where('game_id', $gameId);

        // Filter by activity status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by reset frequency
        if ($request->has('reset_frequency')) {
            $query->where('reset_frequency', $request->reset_frequency);
        }

        // Sort options
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        // Paginate results
        $leaderboards = $query->paginate($request->input('per_page', 10));

        return $this->success($leaderboards);
    }

    /**
     * Create a new leaderboard.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $gameId)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Ensure the game belongs to this developer
        $game = Game::where('game_id', $gameId)
                    ->where('developer_id', $developer->developer_id)
                    ->firstOrFail();

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'score_type' => 'required|in:points,time,distance,kills,custom',
            'sort_order' => 'required|in:ascending,descending',
            'reset_frequency' => 'required|in:never,daily,weekly,monthly,seasonally',
            'is_global' => 'boolean',
            'is_active' => 'boolean',
            'max_entries' => 'nullable|integer|min:1',
            'display_entries' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Calculate next reset time based on frequency
        $nextReset = null;
        if ($request->reset_frequency !== 'never') {
            $nextReset = $this->calculateNextReset($request->reset_frequency);
        }

        // Create the leaderboard
        $leaderboard = Leaderboard::create([
            'game_id' => $gameId,
            'name' => $request->name,
            'description' => $request->description,
            'score_type' => $request->score_type,
            'sort_order' => $request->sort_order,
            'reset_frequency' => $request->reset_frequency,
            'next_reset' => $nextReset,
            'is_global' => $request->input('is_global', true),
            'is_active' => $request->input('is_active', true),
            'max_entries' => $request->input('max_entries'),
            'display_entries' => $request->input('display_entries', 100),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update game's has_leaderboard flag if needed
        if (!$game->has_leaderboard) {
            $game->has_leaderboard = true;
            $game->save();
        }

        return $this->success($leaderboard, 'Leaderboard created successfully');
    }

    /**
     * Update an existing leaderboard.
     *
     * @param Request $request
     * @param int $gameId
     * @param int $leaderboardId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $gameId, $leaderboardId)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Ensure the game belongs to this developer
        $game = Game::where('game_id', $gameId)
                    ->where('developer_id', $developer->developer_id)
                    ->firstOrFail();

        // Find the leaderboard
        $leaderboard = Leaderboard::where('leaderboard_id', $leaderboardId)
                                 ->where('game_id', $gameId)
                                 ->firstOrFail();

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'score_type' => 'sometimes|in:points,time,distance,kills,custom',
            'sort_order' => 'sometimes|in:ascending,descending',
            'reset_frequency' => 'sometimes|in:never,daily,weekly,monthly,seasonally',
            'is_global' => 'boolean',
            'is_active' => 'boolean',
            'max_entries' => 'nullable|integer|min:1',
            'display_entries' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Update fields if provided
        if ($request->has('name')) {
            $leaderboard->name = $request->name;
        }
        
        if ($request->has('description')) {
            $leaderboard->description = $request->description;
        }
        
        if ($request->has('score_type')) {
            $leaderboard->score_type = $request->score_type;
        }
        
        if ($request->has('sort_order')) {
            $leaderboard->sort_order = $request->sort_order;
        }
        
        if ($request->has('reset_frequency')) {
            $leaderboard->reset_frequency = $request->reset_frequency;
            
            // If reset frequency changed, recalculate next reset time
            if ($request->reset_frequency !== 'never') {
                $leaderboard->next_reset = $this->calculateNextReset($request->reset_frequency);
            } else {
                $leaderboard->next_reset = null;
            }
        }
        
        if ($request->has('is_global')) {
            $leaderboard->is_global = $request->is_global;
        }
        
        if ($request->has('is_active')) {
            $leaderboard->is_active = $request->is_active;
        }
        
        if ($request->has('max_entries')) {
            $leaderboard->max_entries = $request->max_entries;
        }
        
        if ($request->has('display_entries')) {
            $leaderboard->display_entries = $request->display_entries;
        }
        
        $leaderboard->updated_at = now();
        $leaderboard->save();

        return $this->success($leaderboard, 'Leaderboard updated successfully');
    }

    /**
     * Delete a leaderboard.
     *
     * @param int $gameId
     * @param int $leaderboardId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($gameId, $leaderboardId)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Ensure the game belongs to this developer
        $game = Game::where('game_id', $gameId)
                    ->where('developer_id', $developer->developer_id)
                    ->firstOrFail();

        // Find the leaderboard
        $leaderboard = Leaderboard::where('leaderboard_id', $leaderboardId)
                                 ->where('game_id', $gameId)
                                 ->firstOrFail();

        // Check if there are entries in this leaderboard
        $hasEntries = LeaderboardEntry::where('leaderboard_id', $leaderboardId)->exists();
        
        if ($hasEntries) {
            // If the leaderboard has entries, just mark it as inactive
            $leaderboard->is_active = false;
            $leaderboard->save();
            
            return $this->success(null, 'Leaderboard has been deactivated as it already has entries');
        } else {
            // If no entries, we can delete it completely
            $leaderboard->delete();
            
            // If this was the last leaderboard, update game's has_leaderboard flag
            $remainingLeaderboards = Leaderboard::where('game_id', $gameId)->count();
            if ($remainingLeaderboards === 0) {
                $game->has_leaderboard = false;
                $game->save();
            }
            
            return $this->success(null, 'Leaderboard deleted successfully');
        }
    }

    /**
     * Get entries for a specific leaderboard.
     *
     * @param Request $request
     * @param int $gameId
     * @param int $leaderboardId
     * @return \Illuminate\Http\JsonResponse
     */
    public function entries(Request $request, $gameId, $leaderboardId)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Ensure the game belongs to this developer
        $game = Game::where('game_id', $gameId)
                    ->where('developer_id', $developer->developer_id)
                    ->firstOrFail();

        // Find the leaderboard
        $leaderboard = Leaderboard::where('leaderboard_id', $leaderboardId)
                                 ->where('game_id', $gameId)
                                 ->firstOrFail();

        $query = LeaderboardEntry::where('leaderboard_id', $leaderboardId)
                                ->where('is_valid', true)
                                ->with('user:user_id,username,display_name,avatar_url');

        // Order entries based on leaderboard sort order
        $orderDirection = $leaderboard->sort_order === 'ascending' ? 'asc' : 'desc';
        $query->orderBy('score', $orderDirection);

        // Paginate results
        $perPage = min($request->input('per_page', 20), $leaderboard->display_entries);
        $entries = $query->paginate($perPage);

        return $this->success($entries);
    }

    /**
     * Reset a leaderboard (clear all entries).
     *
     * @param int $gameId
     * @param int $leaderboardId
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset($gameId, $leaderboardId)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Ensure the game belongs to this developer
        $game = Game::where('game_id', $gameId)
                    ->where('developer_id', $developer->developer_id)
                    ->firstOrFail();

        // Find the leaderboard
        $leaderboard = Leaderboard::where('leaderboard_id', $leaderboardId)
                                 ->where('game_id', $gameId)
                                 ->firstOrFail();

        // Archive or delete existing entries
        LeaderboardEntry::where('leaderboard_id', $leaderboardId)->delete();
        
        // Update leaderboard reset time
        $leaderboard->last_reset = now();
        
        // Calculate next reset time if needed
        if ($leaderboard->reset_frequency !== 'never') {
            $leaderboard->next_reset = $this->calculateNextReset($leaderboard->reset_frequency);
        }
        
        $leaderboard->save();

        return $this->success(null, 'Leaderboard has been reset successfully');
    }

    /**
     * Helper method to calculate next reset time based on frequency.
     *
     * @param string $frequency
     * @return \DateTime
     */
    private function calculateNextReset($frequency)
    {
        $now = now();
        
        switch ($frequency) {
            case 'daily':
                return $now->addDay()->startOfDay();
            case 'weekly':
                return $now->addWeek()->startOfWeek();
            case 'monthly':
                return $now->addMonth()->startOfMonth();
            case 'seasonally':
                // Seasons are typically 3 months
                return $now->addMonths(3)->startOfMonth();
            default:
                return null;
        }
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