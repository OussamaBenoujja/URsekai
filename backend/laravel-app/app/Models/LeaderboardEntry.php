<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderboardEntry extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'leaderboard_entries';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'entry_id';

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
        'leaderboard_id',
        'user_id',
        'score',
        'metadata',
        'rank',
        'submission_time',
        'is_valid',
        'invalidation_reason',
        'ip_address',
        'browser',
        'device_type'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'integer',
        'metadata' => 'array',
        'rank' => 'integer',
        'submission_time' => 'datetime',
        'is_valid' => 'boolean',
    ];

    /**
     * Get the leaderboard that this entry belongs to.
     */
    public function leaderboard()
    {
        return $this->belongsTo(Leaderboard::class, 'leaderboard_id', 'leaderboard_id');
    }

    /**
     * Get the user that owns this entry.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Scope a query to only include valid entries.
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }
}