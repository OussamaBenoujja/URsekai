<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GamePlaytime extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'game_playtime';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'playtime_id';

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
        'user_id',
        'session_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'is_complete',
        'device_type',
        'browser',
        'operating_system',
        'screen_resolution',
        'ip_address',
        'country',
        'city',
        'game_version',
        'events_data'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'is_complete' => 'boolean',
        'events_data' => 'array',
    ];

    /**
     * Get the game that this playtime record belongs to.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    /**
     * Get the user that this playtime record belongs to (may be null for anonymous players).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Scope a query to only include completed sessions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_complete', true);
    }

    /**
     * Scope a query to only include sessions for authenticated users.
     */
    public function scopeAuthenticatedUsers($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope a query to only include sessions from a specific country.
     */
    public function scopeFromCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope a query to filter by device type.
     */
    public function scopeByDeviceType($query, $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }
}