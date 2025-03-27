<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'games';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'game_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'developer_id',
        'title',
        'slug',
        'description',
        'short_description',
        'thumbnail_url',
        'banner_url',
        'logo_url',
        'main_category_id',
        'release_date',
        'last_updated',
        'version',
        'is_published',
        'is_featured',
        'is_approved',
        'approval_date',
        'approved_by',
        'rejection_reason',
        'age_rating',
        'average_rating',
        'total_ratings',
        'total_plays',
        'total_unique_players',
        'total_playtime_minutes',
        'monetization_type',
        'price',
        'currency',
        'sale_price',
        'sale_starts',
        'sale_ends',
        'supports_fullscreen',
        'supports_mobile',
        'minimum_browser_requirements',
        'recommended_browser_requirements',
        'privacy_policy_url',
        'terms_of_service_url',
        'support_email',
        'support_url',
        'game_instructions',
        'game_controls',
        'has_multiplayer',
        'max_players',
        'has_leaderboard',
        'has_achievements',
        'has_in_app_purchases',
        'has_ads',
        'custom_css',
        'custom_javascript',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'release_date' => 'datetime',
        'last_updated' => 'datetime',
        'approval_date' => 'datetime',
        'sale_starts' => 'datetime',
        'sale_ends' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
        'supports_fullscreen' => 'boolean',
        'supports_mobile' => 'boolean',
        'has_multiplayer' => 'boolean',
        'has_leaderboard' => 'boolean',
        'has_achievements' => 'boolean',
        'has_in_app_purchases' => 'boolean',
        'has_ads' => 'boolean',
        'minimum_browser_requirements' => 'array',
        'recommended_browser_requirements' => 'array',
        'price' => 'float',
        'sale_price' => 'float',
        'average_rating' => 'float',
    ];

    /**
     * Get the developer that owns the game.
     */
    public function developer()
    {
        return $this->belongsTo(Developer::class, 'developer_id', 'developer_id');
    }

    /**
     * Get the main category of the game.
     */
    public function mainCategory()
    {
        return $this->belongsTo(GameCategory::class, 'main_category_id', 'category_id');
    }

    /**
     * Get all categories of the game.
     */
    public function categories()
    {
        return $this->belongsToMany(
            GameCategory::class,
            'game_categories_mapping',
            'game_id',
            'category_id'
        );
    }

    /**
     * Get all tags of the game.
     */
    public function tags()
    {
        return $this->belongsToMany(
            GameTag::class,
            'game_tags_mapping',
            'game_id',
            'tag_id'
        );
    }

    /**
     * Get all screenshots of the game.
     */
    public function screenshots()
    {
        return $this->hasMany(GameScreenshot::class, 'game_id', 'game_id');
    }

    /**
     * Get all videos of the game.
     */
    public function videos()
    {
        return $this->hasMany(GameVideo::class, 'game_id', 'game_id');
    }

    /**
     * Get all assets of the game.
     */
    public function assets()
    {
        return $this->hasMany(GameAsset::class, 'game_id', 'game_id');
    }

    /**
     * Get all reviews of the game.
     */
    public function reviews()
    {
        return $this->hasMany(GameReview::class, 'game_id', 'game_id');
    }

    /**
     * Get all achievements of the game.
     */
    public function achievements()
    {
        return $this->hasMany(Achievement::class, 'game_id', 'game_id');
    }

    /**
     * Get all leaderboards of the game.
     */
    public function leaderboards()
    {
        return $this->hasMany(Leaderboard::class, 'game_id', 'game_id');
    }

    /**
     * Get all levels of the game.
     */
    public function levels()
    {
        return $this->hasMany(GameLevel::class, 'game_id', 'game_id')
            ->orderBy('level_number', 'asc');
    }

    /**
     * Get users who favorited the game.
     */
    public function favoritedBy()
    {
        return $this->belongsToMany(
            User::class,
            'game_favorites',
            'game_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Get the user who approved the game.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    /**
     * Scope a query to only include published games.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                     ->where('is_approved', true);
    }

    /**
     * Scope a query to only include featured games.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                     ->where('is_published', true)
                     ->where('is_approved', true);
    }

    /**
     * Scope a query to filter games by category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    /**
     * Scope a query to filter games by tag.
     */
    public function scopeWithTag($query, $tagId)
    {
        return $query->whereHas('tags', function ($q) use ($tagId) {
            $q->where('tag_id', $tagId);
        });
    }

    /**
     * Scope a query to filter games by multiple tags.
     */
    public function scopeWithTags($query, array $tagIds)
    {
        return $query->whereHas('tags', function ($q) use ($tagIds) {
            $q->whereIn('tag_id', $tagIds);
        });
    }

    /**
     * Scope a query to include games with price less than or equal to given value.
     */
    public function scopeMaxPrice($query, $price)
    {
        return $query->where(function ($q) use ($price) {
            $q->where('price', '<=', $price)
              ->orWhereNull('price')
              ->orWhere('price', 0);
        });
    }

    /**
     * Scope a query to only include free games.
     */
    public function scopeFree($query)
    {
        return $query->where(function ($q) {
            $q->where('monetization_type', 'free')
              ->orWhere('price', 0)
              ->orWhereNull('price');
        });
    }

    /**
     * Scope a query to search games by title or description.
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'LIKE', "%{$searchTerm}%")
              ->orWhere('short_description', 'LIKE', "%{$searchTerm}%")
              ->orWhere('description', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Get the current sale status of the game.
     */
    public function getIsOnSaleAttribute()
    {
        if (!$this->sale_price || !$this->sale_starts || !$this->sale_ends) {
            return false;
        }

        $now = now();
        return $this->sale_price < $this->price &&
               $now->gte($this->sale_starts) &&
               $now->lte($this->sale_ends);
    }

    /**
     * Get the current price of the game (considering sales).
     */
    public function getCurrentPriceAttribute()
    {
        if ($this->getIsOnSaleAttribute()) {
            return $this->sale_price;
        }
        
        return $this->price;
    }

    /**
     * Check if game is free.
     */
    public function getIsFreeAttribute()
    {
        return $this->monetization_type === 'free' || 
               $this->price === 0 || 
               $this->price === null;
    }
}