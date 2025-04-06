<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGameProgress extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_game_progress';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'progress_id';

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
        'current_level',
        'highest_level_reached',
        'total_score',
        'highest_score',
        'total_time_played_minutes',
        'xp_earned',
        'in_game_currency',
        'achievements_unlocked',
        'total_achievements',
        'game_specific_data',
        'last_played',
        'first_played',
        'times_played',
        'save_data'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_level' => 'integer',
        'highest_level_reached' => 'integer',
        'total_score' => 'integer',
        'highest_score' => 'integer',
        'total_time_played_minutes' => 'integer',
        'xp_earned' => 'integer',
        'in_game_currency' => 'integer',
        'achievements_unlocked' => 'integer',
        'total_achievements' => 'integer',
        'game_specific_data' => 'array',
        'last_played' => 'datetime',
        'first_played' => 'datetime',
        'times_played' => 'integer',
    ];

    /**
     * Get the user for this progress record.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the game for this progress record.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    /**
     * Calculate completion percentage based on achievements.
     */
    public function getCompletionPercentageAttribute()
    {
        if ($this->total_achievements > 0) {
            return round(($this->achievements_unlocked / $this->total_achievements) * 100, 2);
        }
        
        return 0;
    }

    /**
     * Check if user has played recently (within the last 7 days).
     */
    public function getHasPlayedRecentlyAttribute()
    {
        if (!$this->last_played) {
            return false;
        }
        
        return $this->last_played->gt(now()->subDays(7));
    }
}