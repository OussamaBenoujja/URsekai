<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Developer;
use App\Models\GameCategory;
use App\Models\GameTag;
use App\Models\GameAsset;
use App\Models\GameScreenshot;
use App\Models\GameVideo;
use App\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DeveloperGameController extends Controller
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
     * Get all games for the authenticated developer.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        $query = Game::where('developer_id', $developer->developer_id);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            } elseif ($request->status === 'pending_approval') {
                $query->where('is_published', true)
                      ->where('is_approved', false);
            }
        }

        // Sort options
        $sortField = $request->input('sort', 'updated_at');
        $sortDirection = $request->input('direction', 'desc');

        // Validate sort field
        $allowedSortFields = [
            'title', 'release_date', 'created_at', 
            'updated_at', 'total_plays', 'average_rating'
        ];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'updated_at';
        }

        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        // Include relationships
        $query->with(['mainCategory', 'screenshots' => function($q) {
            $q->active()->ordered()->limit(1);
        }]);

        // Paginate results
        $games = $query->paginate($request->input('per_page', 20));

        return $this->success($games);
    }

    /**
     * Get detail statistics and information for a developer's game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $gameId)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Ensure the game belongs to this developer
        $game = Game::where('game_id', $gameId)
                    ->where('developer_id', $developer->developer_id)
                    ->with([
                        'mainCategory',
                        'categories',
                        'tags',
                        'screenshots' => function($q) {
                            $q->active()->ordered();
                        },
                        'videos',
                        'assets',
                        'achievements',
                        'leaderboards',
                        'reviews' => function($q) {
                            $q->visible()->orderBy('created_at', 'desc')->limit(5);
                        }
                    ])
                    ->firstOrFail();

        // Get metrics for last 30 days
        $thirtyDaysAgo = now()->subDays(30)->format('Y-m-d');
        
        $metrics = \App\Models\AnalyticsGameMetrics::where('game_id', $gameId)
                                                ->where('date', '>=', $thirtyDaysAgo)
                                                ->orderBy('date', 'asc')
                                                ->get();

        $game->recent_metrics = $metrics;

        // Get revenue data
        if ($game->monetization_type !== 'free') {
            $revenue = \App\Models\Transaction::where('game_id', $gameId)
                                          ->where('status', 'completed')
                                          ->selectRaw('SUM(amount) as total, COUNT(*) as count, DATE(created_at) as date')
                                          ->where('created_at', '>=', now()->subDays(30))
                                          ->groupBy('date')
                                          ->orderBy('date', 'asc')
                                          ->get();
                                          
            $game->revenue_data = $revenue;
            $game->total_revenue = \App\Models\Transaction::where('game_id', $gameId)
                                                      ->where('status', 'completed')
                                                      ->sum('amount');
        }

        return $this->success($game);
    }

    /**
     * Create a new game.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'short_description' => 'required|string|max:255',
            'description' => 'required|string',
            'main_category_id' => 'required|exists:game_categories,category_id',
            'age_rating' => 'required|in:E,E10+,T,M,A',
            'monetization_type' => 'required|in:free,premium,freemium,ads,subscription',
            'price' => 'required_if:monetization_type,premium,freemium,subscription|nullable|numeric|min:0',
            'currency' => 'required_if:monetization_type,premium,freemium,subscription|nullable|string|size:3',
            'supports_fullscreen' => 'boolean',
            'supports_mobile' => 'boolean',
            'has_multiplayer' => 'boolean',
            'max_players' => 'integer|min:1',
            'game_instructions' => 'nullable|string',
            'game_controls' => 'nullable|string',
            'privacy_policy_url' => 'nullable|url',
            'terms_of_service_url' => 'nullable|url',
            'support_email' => 'nullable|email',
            'support_url' => 'nullable|url',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'categories' => 'array',
            'categories.*' => 'exists:game_categories,category_id',
            'tags' => 'array',
            'tags.*' => 'exists:game_tags,tag_id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Upload thumbnail
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('games/thumbnails', 'public');
            $thumbnailPath = '/storage/' . $thumbnailPath;
        }

        // Create slug from title
        $slug = Str::slug($request->title);
        $baseSlug = $slug;
        $counter = 1;
        
        // Make sure slug is unique
        while (Game::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Create the game
        $game = Game::create([
            'developer_id' => $developer->developer_id,
            'title' => $request->title,
            'slug' => $slug,
            'short_description' => $request->short_description,
            'description' => $request->description,
            'thumbnail_url' => $thumbnailPath,
            'main_category_id' => $request->main_category_id,
            'age_rating' => $request->age_rating,
            'monetization_type' => $request->monetization_type,
            'price' => $request->monetization_type !== 'free' ? $request->price : null,
            'currency' => $request->monetization_type !== 'free' ? $request->currency : null,
            'supports_fullscreen' => $request->input('supports_fullscreen', true),
            'supports_mobile' => $request->input('supports_mobile', false),
            'has_multiplayer' => $request->input('has_multiplayer', false),
            'max_players' => $request->input('max_players', 1),
            'game_instructions' => $request->game_instructions,
            'game_controls' => $request->game_controls,
            'privacy_policy_url' => $request->privacy_policy_url,
            'terms_of_service_url' => $request->terms_of_service_url,
            'support_email' => $request->support_email,
            'support_url' => $request->support_url,
            'is_published' => false,
            'is_approved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attach categories
        if ($request->has('categories')) {
            $game->categories()->attach($request->categories);
        }

        // Attach tags
        if ($request->has('tags')) {
            $game->tags()->attach($request->tags);
        }

        return $this->success($game, 'Game created successfully');
    }

    /**
     * Update an existing game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $gameId)
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
            'title' => 'sometimes|string|max:100',
            'short_description' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'main_category_id' => 'sometimes|exists:game_categories,category_id',
            'age_rating' => 'sometimes|in:E,E10+,T,M,A',
            'monetization_type' => 'sometimes|in:free,premium,freemium,ads,subscription',
            'price' => 'required_if:monetization_type,premium,freemium,subscription|nullable|numeric|min:0',
            'currency' => 'required_if:monetization_type,premium,freemium,subscription|nullable|string|size:3',
            'supports_fullscreen' => 'boolean',
            'supports_mobile' => 'boolean',
            'has_multiplayer' => 'boolean',
            'max_players' => 'integer|min:1',
            'game_instructions' => 'nullable|string',
            'game_controls' => 'nullable|string',
            'privacy_policy_url' => 'nullable|url',
            'terms_of_service_url' => 'nullable|url',
            'support_email' => 'nullable|email',
            'support_url' => 'nullable|url',
            'thumbnail' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'categories' => 'array',
            'categories.*' => 'exists:game_categories,category_id',
            'tags' => 'array',
            'tags.*' => 'exists:game_tags,tag_id',
            'version' => 'sometimes|string|max:20',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_starts' => 'nullable|date',
            'sale_ends' => 'nullable|date|after:sale_starts',
            'has_achievements' => 'boolean',
            'has_leaderboard' => 'boolean',
            'has_in_app_purchases' => 'boolean',
            'has_ads' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Upload new thumbnail if provided
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($game->thumbnail_url && !str_contains($game->thumbnail_url, 'default')) {
                $oldPath = str_replace('/storage/', '', $game->thumbnail_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            
            $thumbnailPath = $request->file('thumbnail')->store('games/thumbnails', 'public');
            $game->thumbnail_url = '/storage/' . $thumbnailPath;
        }

        // Update game fields
        if ($request->has('title')) {
            $game->title = $request->title;
            
            // Update slug if title changes
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

        // Update other fields if provided
        $fillableFields = [
            'short_description', 'description', 'main_category_id', 'age_rating',
            'monetization_type', 'price', 'currency', 'supports_fullscreen',
            'supports_mobile', 'has_multiplayer', 'max_players', 'game_instructions',
            'game_controls', 'privacy_policy_url', 'terms_of_service_url',
            'support_email', 'support_url', 'version', 'sale_price', 'sale_starts',
            'sale_ends', 'has_achievements', 'has_leaderboard', 'has_in_app_purchases',
            'has_ads'
        ];

        foreach ($fillableFields as $field) {
            if ($request->has($field)) {
                $game->{$field} = $request->{$field};
            }
        }

        // Always update the 'updated_at' timestamp
        $game->updated_at = now();
        $game->save();

        // Update categories if provided
        if ($request->has('categories')) {
            $game->categories()->sync($request->categories);
        }

        // Update tags if provided
        if ($request->has('tags')) {
            $game->tags()->sync($request->tags);
        }

        return $this->success($game, 'Game updated successfully');
    }

    /**
     * Upload game assets (WebGL files, etc.).
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAssets(Request $request, $gameId)
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
            'asset_type' => 'required|in:main_game,texture,sound,model,script,other',
            'asset_file' => 'required|file|max:102400', // 100MB max file size
            'version' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Check platform settings for max file size
        $maxFileSizeMB = \App\Models\SystemSetting::where('category', 'game')
                                                ->where('name', 'max_game_file_size_mb')
                                                ->value('value') ?? 100;
                                                
        $maxFileSize = $maxFileSizeMB * 1024 * 1024; // Convert to bytes
        
        if ($request->file('asset_file')->getSize() > $maxFileSize) {
            return $this->error("File size exceeds the maximum allowed size of {$maxFileSizeMB}MB", 422);
        }

        // Check allowed file types
        $allowedTypes = \App\Models\SystemSetting::where('category', 'game')
                                             ->where('name', 'allowed_game_file_types')
                                             ->value('value');
                                             
        // If no setting found, allow these types by default
        if (!$allowedTypes) {
            $allowedTypes = 'js,json,wasm,bin,data,unity3d,mem,jpg,png,mp3,ogg,wav';
        }
        
        $allowedExtensions = explode(',', $allowedTypes);
        $fileExtension = $request->file('asset_file')->getClientOriginalExtension();
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return $this->error("File type '{$fileExtension}' is not allowed", 422);
        }

        // Create directory path based on game ID
        $path = "games/{$gameId}/assets";
        
        // Get file details
        $file = $request->file('asset_file');
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $fileMimeType = $file->getMimeType();
        
        // If it's a main game file, archive old versions
        if ($request->asset_type === 'main_game') {
            $oldMainAssets = GameAsset::where('game_id', $gameId)
                                    ->where('asset_type', 'main_game')
                                    ->where('is_active', true)
                                    ->get();
                                    
            foreach ($oldMainAssets as $oldAsset) {
                $oldAsset->is_active = false;
                $oldAsset->save();
            }
        }
        
        // Store the file
        $filePath = $file->store($path, 'public');
        
        // Calculate checksum (md5 hash)
        $checksum = md5_file(storage_path('app/public/' . $filePath));
        
        // Create the asset record
        $asset = GameAsset::create([
            'game_id' => $gameId,
            'asset_type' => $request->asset_type,
            'file_name' => $fileName,
            'file_path' => '/storage/' . $filePath,
            'file_size_bytes' => $fileSize,
            'file_extension' => $fileExtension,
            'mime_type' => $fileMimeType,
            'checksum' => $checksum,
            'version' => $request->version ?? $game->version,
            'uploaded_at' => now(),
            'is_compressed' => false, // Could add logic to determine this
            'is_active' => true,
        ]);
        
        // If this is a main game asset, update the game's version
        if ($request->asset_type === 'main_game' && $request->version) {
            $game->version = $request->version;
            $game->last_updated = now();
            $game->save();
        }

        return $this->success($asset, 'Asset uploaded successfully');
    }

    /**
     * Upload game screenshots.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadScreenshots(Request $request, $gameId)
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
            'screenshots' => 'required|array|min:1|max:10',
            'screenshots.*' => 'image|mimes:jpeg,png,jpg|max:5120', // 5MB per image
            'captions' => 'array',
            'captions.*' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $uploadedScreenshots = [];
        
        // Get next display order
        $maxOrder = GameScreenshot::where('game_id', $gameId)->max('display_order') ?? 0;
        
        // Process each screenshot
        foreach ($request->file('screenshots') as $index => $screenshot) {
            // Get image dimensions
            $dimensions = getimagesize($screenshot);
            $width = $dimensions[0];
            $height = $dimensions[1];
            
            // Store the image
            $path = $screenshot->store("games/{$gameId}/screenshots", 'public');
            
            // Create a thumbnail version
            // This would typically use an image manipulation library like Intervention Image
            // For simplicity, we're just using the same image here
            $thumbnailPath = $path;
            
            // Get caption if provided
            $caption = isset($request->captions[$index]) ? $request->captions[$index] : null;
            
            // Create the screenshot record
            $screenshotModel = GameScreenshot::create([
                'game_id' => $gameId,
                'image_url' => '/storage/' . $path,
                'thumbnail_url' => '/storage/' . $thumbnailPath,
                'caption' => $caption,
                'width' => $width,
                'height' => $height,
                'display_order' => $maxOrder + $index + 1,
                'is_active' => true,
                'created_at' => now(),
            ]);
            
            $uploadedScreenshots[] = $screenshotModel;
        }

        return $this->success($uploadedScreenshots, 'Screenshots uploaded successfully');
    }

    /**
     * Delete a game screenshot.
     *
     * @param int $gameId
     * @param int $screenshotId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteScreenshot($gameId, $screenshotId)
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

        // Find the screenshot
        $screenshot = GameScreenshot::where('screenshot_id', $screenshotId)
                                  ->where('game_id', $gameId)
                                  ->firstOrFail();

        // Delete the physical files
        $imagePath = str_replace('/storage/', '', $screenshot->image_url);
        $thumbnailPath = str_replace('/storage/', '', $screenshot->thumbnail_url);
        
        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
        
        if ($thumbnailPath !== $imagePath && Storage::disk('public')->exists($thumbnailPath)) {
            Storage::disk('public')->delete($thumbnailPath);
        }
        
        // Delete the database record
        $screenshot->delete();
        
        // Reorder remaining screenshots
        $remainingScreenshots = GameScreenshot::where('game_id', $gameId)
                                             ->orderBy('display_order', 'asc')
                                             ->get();
                                             
        $order = 1;
        foreach ($remainingScreenshots as $screenshot) {
            $screenshot->display_order = $order++;
            $screenshot->save();
        }

        return $this->success(null, 'Screenshot deleted successfully');
    }

    /**
     * Publish a game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function publish(Request $request, $gameId)
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

        // Check if the game has required assets to be published
        $mainGameAsset = GameAsset::where('game_id', $gameId)
                                 ->where('asset_type', 'main_game')
                                 ->where('is_active', true)
                                 ->exists();
                                 
        if (!$mainGameAsset) {
            return $this->error('Cannot publish: game requires a main game asset', 422);
        }
        
        // Check if game has at least one screenshot
        $hasScreenshot = GameScreenshot::where('game_id', $gameId)->exists();
        if (!$hasScreenshot) {
            return $this->error('Cannot publish: game requires at least one screenshot', 422);
        }
        
        // Set publish status
        $game->is_published = true;
        
        // Set release date if this is first-time publishing
        if (!$game->release_date) {
            $game->release_date = now();
        }
        
        // If game wasn't previously published, set status to pending approval
        if ($game->is_approved) {
            // If game was already approved before, it stays approved
            $game->is_approved = true;
        } else {
            // Needs new approval
            $game->is_approved = false;
        }
        
        $game->save();
        
        // Update developer stats
        if (!$game->release_date) {
            $developer->total_games_published = $developer->total_games_published + 1;
            $developer->save();
        }

        return $this->success($game, 'Game published successfully');
    }

    /**
     * Unpublish a game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unpublish(Request $request, $gameId)
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

        // Set publish status
        $game->is_published = false;
        $game->save();

        return $this->success($game, 'Game unpublished successfully');
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