<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'leaderboards';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'leaderboard_id';

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
        'score_type',
        'sort_order',
        'reset_frequency',
        'last_reset',
        'next_reset',
        'is_global',
        'is_active',
        'max_entries',
        'display_entries',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'max_entries' => 'integer',
        'display_entries' => 'integer',
        'last_reset' => 'datetime',
        'next_reset' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the game that owns the leaderboard.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    /**
     * Get the entries for this leaderboard.
     */
    public function entries()
    {
        return $this->hasMany(LeaderboardEntry::class, 'leaderboard_id', 'leaderboard_id');
    }

    /**
     * Scope a query to only include active leaderboards.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include global leaderboards.
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }
}