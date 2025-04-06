<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameScreenshot extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'game_screenshots';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'screenshot_id';

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
        'image_url',
        'thumbnail_url',
        'caption',
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
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the game that owns the screenshot.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    /**
     * Scope a query to only include active screenshots.
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
}