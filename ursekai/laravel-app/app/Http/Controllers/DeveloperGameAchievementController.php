<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Developer;
use App\Models\Achievement;
use App\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DeveloperGameAchievementController extends Controller
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
     * Get all achievements for a specific game.
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

        $query = Achievement::where('game_id', $gameId);

        // Filter by visibility
        if ($request->has('visibility')) {
            if ($request->visibility === 'hidden') {
                $query->where('is_hidden', true);
            } elseif ($request->visibility === 'visible') {
                $query->where('is_hidden', false);
            }
        }

        // Filter by difficulty
        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        // Sort options
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        // Validate sort field
        $allowedSortFields = [
            'name', 'created_at', 'updated_at', 'points', 'total_unlocks'
        ];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        // Paginate results
        $achievements = $query->paginate($request->input('per_page', 20));

        return $this->success($achievements);
    }

    /**
     * Create a new achievement for a game.
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
            'description' => 'required|string',
            'points' => 'required|integer|min:0',
            'difficulty' => 'required|in:easy,medium,hard,extreme',
            'is_hidden' => 'boolean',
            'unlock_criteria' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Upload icon if provided
        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store("games/{$gameId}/achievements", 'public');
            $iconPath = '/storage/' . $iconPath;
        }

        // Create the achievement
        $achievement = Achievement::create([
            'game_id' => $gameId,
            'name' => $request->name,
            'description' => $request->description,
            'icon_url' => $iconPath,
            'points' => $request->points,
            'difficulty' => $request->difficulty,
            'is_hidden' => $request->input('is_hidden', false),
            'is_active' => true,
            'unlock_criteria' => $request->unlock_criteria,
            'total_unlocks' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update game's has_achievements flag if needed
        if (!$game->has_achievements) {
            $game->has_achievements = true;
            $game->save();
        }

        return $this->success($achievement, 'Achievement created successfully');
    }

    /**
     * Update an existing achievement.
     *
     * @param Request $request
     * @param int $gameId
     * @param int $achievementId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $gameId, $achievementId)
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

        // Find the achievement
        $achievement = Achievement::where('achievement_id', $achievementId)
                                ->where('game_id', $gameId)
                                ->firstOrFail();

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'description' => 'sometimes|string',
            'points' => 'sometimes|integer|min:0',
            'difficulty' => 'sometimes|in:easy,medium,hard,extreme',
            'is_hidden' => 'boolean',
            'is_active' => 'boolean',
            'unlock_criteria' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Upload new icon if provided
        if ($request->hasFile('icon')) {
            // Delete old icon if exists
            if ($achievement->icon_url) {
                $oldPath = str_replace('/storage/', '', $achievement->icon_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            
            $iconPath = $request->file('icon')->store("games/{$gameId}/achievements", 'public');
            $achievement->icon_url = '/storage/' . $iconPath;
        }

        // Update fields if provided
        if ($request->has('name')) {
            $achievement->name = $request->name;
        }
        
        if ($request->has('description')) {
            $achievement->description = $request->description;
        }
        
        if ($request->has('points')) {
            $achievement->points = $request->points;
        }
        
        if ($request->has('difficulty')) {
            $achievement->difficulty = $request->difficulty;
        }
        
        if ($request->has('is_hidden')) {
            $achievement->is_hidden = $request->is_hidden;
        }
        
        if ($request->has('is_active')) {
            $achievement->is_active = $request->is_active;
        }
        
        if ($request->has('unlock_criteria')) {
            $achievement->unlock_criteria = $request->unlock_criteria;
        }
        
        $achievement->updated_at = now();
        $achievement->save();

        return $this->success($achievement, 'Achievement updated successfully');
    }

    /**
     * Delete an achievement.
     *
     * @param int $gameId
     * @param int $achievementId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($gameId, $achievementId)
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

        // Find the achievement
        $achievement = Achievement::where('achievement_id', $achievementId)
                                ->where('game_id', $gameId)
                                ->firstOrFail();

        // Check if any users have unlocked this achievement
        $hasUnlocks = $achievement->total_unlocks > 0;
        
        if ($hasUnlocks) {
            // If the achievement has been unlocked by users, just mark it as inactive
            $achievement->is_active = false;
            $achievement->save();
            
            return $this->success(null, 'Achievement has been deactivated as it has already been unlocked by users');
        } else {
            // If no unlocks, we can delete it completely
            // Delete icon if exists
            if ($achievement->icon_url) {
                $iconPath = str_replace('/storage/', '', $achievement->icon_url);
                if (Storage::disk('public')->exists($iconPath)) {
                    Storage::disk('public')->delete($iconPath);
                }
            }
            
            $achievement->delete();
            
            // If this was the last achievement, update game's has_achievements flag
            $remainingAchievements = Achievement::where('game_id', $gameId)->count();
            if ($remainingAchievements === 0) {
                $game->has_achievements = false;
                $game->save();
            }
            
            return $this->success(null, 'Achievement deleted successfully');
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