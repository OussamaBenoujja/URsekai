<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GameTag;
use App\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminGameTagController extends Controller
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
     * Display a listing of the tags.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = GameTag::query();

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // Sort options
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');

        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        // Include game counts
        $query->withCount('games');

        // Paginate or get all
        if ($request->has('per_page')) {
            $tags = $query->paginate($request->per_page);
        } else {
            $tags = $query->get();
        }

        return $this->success($tags);
    }

    /**
     * Store a newly created tag.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:game_tags',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Create tag
        $tag = GameTag::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->input('is_active', true),
            'created_at' => now()
        ]);

        return $this->success($tag, 'Tag created successfully', 201);
    }

    /**
     * Display the specified tag.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $tag = GameTag::with('games')->findOrFail($id);
        return $this->success($tag);
    }

    /**
     * Update the specified tag.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $tag = GameTag::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50|unique:game_tags,name,' . $id . ',tag_id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Update fields if provided
        if ($request->has('name')) {
            $tag->name = $request->name;
        }
        
        if ($request->has('description')) {
            $tag->description = $request->description;
        }
        
        if ($request->has('is_active')) {
            $tag->is_active = $request->is_active;
        }
        
        $tag->save();

        return $this->success($tag, 'Tag updated successfully');
    }

    /**
     * Remove the specified tag.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $tag = GameTag::findOrFail($id);
        
        // Check if tag has games
        $gamesCount = $tag->games()->count();
        
        if ($gamesCount > 0) {
            // If tag has games, just deactivate it
            $tag->is_active = false;
            $tag->save();
            
            return $this->success(null, 'Tag has been deactivated as it is associated with games');
        }
        
        // Delete tag
        $tag->delete();
        
        return $this->success(null, 'Tag deleted successfully');
    }

    /**
     * Bulk create tags.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tags' => 'required|array',
            'tags.*' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $createdTags = [];
        $existingTags = [];

        foreach ($request->tags as $tagName) {
            // Check if tag already exists
            $existingTag = GameTag::where('name', $tagName)->first();
            
            if ($existingTag) {
                $existingTags[] = $existingTag;
                continue;
            }
            
            // Create new tag
            $tag = GameTag::create([
                'name' => $tagName,
                'is_active' => true,
                'created_at' => now()
            ]);
            
            $createdTags[] = $tag;
        }

        return $this->success([
            'created' => $createdTags,
            'existing' => $existingTags
        ], count($createdTags) . ' tags created successfully');
    }

    /**
     * Bulk update tags activation status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'required|integer|exists:game_tags,tag_id',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Update status for all specified tags
        GameTag::whereIn('tag_id', $request->tag_ids)
               ->update(['is_active' => $request->is_active]);

        return $this->success(null, count($request->tag_ids) . ' tags updated successfully');
    }

    /**
     * Get tag popularity (most used tags).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function popular(Request $request)
    {
        $limit = $request->input('limit', 20);
        
        $tags = GameTag::active()
                     ->withCount('games')
                     ->having('games_count', '>', 0)
                     ->orderBy('games_count', 'desc')
                     ->take($limit)
                     ->get();

        return $this->success($tags);
    }

    /**
     * Search tags.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $tags = GameTag::active()
                     ->where('name', 'LIKE', '%' . $request->query . '%')
                     ->withCount('games')
                     ->orderBy('games_count', 'desc')
                     ->take(20)
                     ->get();

        return $this->success($tags);
    }
}