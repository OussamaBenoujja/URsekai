<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameVideo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'game_videos';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'video_id';

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
        'game_id',
        'video_type',
        'title',
        'description',
        'video_url',
        'thumbnail_url',
        'duration_seconds',
        'width',
        'height',
        'display_order',
        'is_active',
        'created_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration_seconds' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the game that owns the video.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    /**
     * Scope a query to only include active videos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }

    /**
     * Scope a query to filter by video type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('video_type', $type);
    }

    /**
     * Format duration in human-readable form (MM:SS).
     */
    public function getFormattedDurationAttribute()
    {
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}