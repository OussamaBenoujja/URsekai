<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GameCategory;
use App\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminGameCategoryController extends Controller
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
     * Display a listing of the categories.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = GameCategory::query();

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // Sort options
        $sortField = $request->input('sort', 'display_order');
        $sortDirection = $request->input('direction', 'asc');

        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        // Include game counts
        $query->withCount('games');

        // Paginate or get all
        if ($request->has('per_page')) {
            $categories = $query->paginate($request->per_page);
        } else {
            $categories = $query->get();
        }

        return $this->success($categories);
    }

    /**
     * Store a newly created category.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:game_categories',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'display_order' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Upload icon if provided
        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('categories', 'public');
            $iconPath = '/storage/' . $iconPath;
        }

        // Get max display order if not provided
        if (!$request->has('display_order')) {
            $maxOrder = GameCategory::max('display_order') ?? 0;
            $displayOrder = $maxOrder + 1;
        } else {
            $displayOrder = $request->display_order;
        }

        // Create category
        $category = GameCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'icon_url' => $iconPath,
            'display_order' => $displayOrder,
            'is_active' => $request->input('is_active', true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success($category, 'Category created successfully', 201);
    }

    /**
     * Display the specified category.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $category = GameCategory::with('games')->findOrFail($id);
        return $this->success($category);
    }

    /**
     * Update the specified category.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $category = GameCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50|unique:game_categories,name,' . $id . ',category_id',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'display_order' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Upload icon if provided
        if ($request->hasFile('icon')) {
            // Delete old icon
            if ($category->icon_url) {
                $oldPath = str_replace('/storage/', '', $category->icon_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            
            $iconPath = $request->file('icon')->store('categories', 'public');
            $category->icon_url = '/storage/' . $iconPath;
        }

        // Update fields if provided
        if ($request->has('name')) {
            $category->name = $request->name;
        }
        
        if ($request->has('description')) {
            $category->description = $request->description;
        }
        
        if ($request->has('display_order')) {
            $category->display_order = $request->display_order;
        }
        
        if ($request->has('is_active')) {
            $category->is_active = $request->is_active;
        }
        
        $category->updated_at = now();
        $category->save();

        return $this->success($category, 'Category updated successfully');
    }

    /**
     * Remove the specified category.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $category = GameCategory::findOrFail($id);
        
        // Check if category has games
        $gamesCount = $category->games()->count();
        $isMainCategory = $category->mainGames()->count() > 0;
        
        if ($isMainCategory) {
            return $this->error('Cannot delete category as it is used as a main category for some games', 422);
        }
        
        if ($gamesCount > 0) {
            // If category has games, just deactivate it
            $category->is_active = false;
            $category->save();
            
            return $this->success(null, 'Category has been deactivated as it is associated with games');
        }
        
        // Delete icon if exists
        if ($category->icon_url) {
            $iconPath = str_replace('/storage/', '', $category->icon_url);
            if (Storage::disk('public')->exists($iconPath)) {
                Storage::disk('public')->delete($iconPath);
            }
        }
        
        // Delete category
        $category->delete();
        
        return $this->success(null, 'Category deleted successfully');
    }

    /**
     * Reorder categories.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:game_categories,category_id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Update order
        foreach ($request->order as $index => $categoryId) {
            GameCategory::where('category_id', $categoryId)
                       ->update(['display_order' => $index + 1]);
        }

        return $this->success(null, 'Categories reordered successfully');
    }
}