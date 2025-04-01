<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameLevel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'game_levels';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'level_id';

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
        'level_number',
        'name',
        'description',
        'difficulty',
        'xp_reward',
        'currency_reward',
        'unlock_criteria',
        'is_hidden',
        'icon_url',
        'thumbnail_url',
        'time_limit_seconds',
        'is_active',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level_number' => 'integer',
        'xp_reward' => 'integer',
        'currency_reward' => 'integer',
        'time_limit_seconds' => 'integer',
        'is_hidden' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the game that owns the level.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    /**
     * Scope a query to only include active levels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include visible levels.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Get the formatted time limit.
     */
    public function getFormattedTimeLimitAttribute()
    {
        if (!$this->time_limit_seconds) {
            return 'No time limit';
        }
        
        $minutes = floor($this->time_limit_seconds / 60);
        $seconds = $this->time_limit_seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get the next level in sequence.
     */
    public function nextLevel()
    {
        return GameLevel::where('game_id', $this->game_id)
                      ->where('level_number', $this->level_number + 1)
                      ->first();
    }

    /**
     * Get the previous level in sequence.
     */
    public function previousLevel()
    {
        return GameLevel::where('game_id', $this->game_id)
                      ->where('level_number', $this->level_number - 1)
                      ->first();
    }
}