<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'achievements';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'achievement_id';

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
        'name',
        'description',
        'icon_url',
        'points',
        'difficulty',
        'is_hidden',
        'is_active',
        'unlock_criteria',
        'total_unlocks',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_hidden' => 'boolean',
        'is_active' => 'boolean',
        'points' => 'integer',
        'total_unlocks' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the game that owns the achievement.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    /**
     * Get the users who have unlocked this achievement.
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_achievements',
            'achievement_id',
            'user_id'
        )->withPivot('unlocked_at', 'game_state_data');
    }

    /**
     * Get user achievement records.
     */
    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class, 'achievement_id', 'achievement_id');
    }

    /**
     * Scope a query to only include active achievements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include visible achievements.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }
}