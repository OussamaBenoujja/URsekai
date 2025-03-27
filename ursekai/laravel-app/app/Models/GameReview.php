<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameReview extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'game_reviews';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'review_id';

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
        'user_id',
        'game_id',
        'rating',
        'title',
        'content',
        'has_spoilers',
        'playtime_at_review_minutes',
        'upvotes',
        'downvotes',
        'is_verified_purchase',
        'is_verified_player',
        'is_featured',
        'is_hidden',
        'hide_reason',
        'device_type',
        'browser',
        'operating_system',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'float',
        'has_spoilers' => 'boolean',
        'is_verified_purchase' => 'boolean',
        'is_verified_player' => 'boolean',
        'is_featured' => 'boolean',
        'is_hidden' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who wrote the review.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the game that was reviewed.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    /**
     * Get the comments on this review.
     */
    public function comments()
    {
        return $this->hasMany(ReviewComment::class, 'review_id', 'review_id');
    }

    /**
     * Get the votes on this review.
     */
    public function votes()
    {
        return $this->hasMany(ReviewVote::class, 'review_id', 'review_id');
    }

    /**
     * Scope a query to only include visible reviews.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope a query to only include featured reviews.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                     ->where('is_hidden', false);
    }

    /**
     * Scope a query to order by helpfulness (upvotes - downvotes).
     */
    public function scopeByHelpfulness($query)
    {
        return $query->orderByRaw('(upvotes - downvotes) DESC');
    }

    /**
     * Scope a query to only include recent reviews.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get net votes (upvotes - downvotes).
     */
    public function getNetVotesAttribute()
    {
        return $this->upvotes - $this->downvotes;
    }
}