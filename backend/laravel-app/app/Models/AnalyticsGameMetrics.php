<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsGameMetrics extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analytics_game_metrics';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'metric_id';

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
        'date',
        'total_plays',
        'unique_players',
        'new_players',
        'average_playtime_minutes',
        'total_playtime_minutes',
        'completions',
        'conversion_rate',
        'revenue',
        'ad_impressions',
        'ad_clicks',
        'ad_revenue',
        'ratings_count',
        'average_rating',
        'shares',
        'achievement_unlocks',
        'level_ups',
        'in_app_purchases',
        'peak_concurrent_players',
        'browser_data',
        'device_data',
        'country_data',
        'referrer_data',
        'retention_data',
        'created_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'total_plays' => 'integer',
        'unique_players' => 'integer',
        'new_players' => 'integer',
        'average_playtime_minutes' => 'float',
        'total_playtime_minutes' => 'integer',
        'completions' => 'integer',
        'conversion_rate' => 'float',
        'revenue' => 'float',
        'ad_impressions' => 'integer',
        'ad_clicks' => 'integer',
        'ad_revenue' => 'float',
        'ratings_count' => 'integer',
        'average_rating' => 'float',
        'shares' => 'integer',
        'achievement_unlocks' => 'integer',
        'level_ups' => 'integer',
        'in_app_purchases' => 'integer',
        'peak_concurrent_players' => 'integer',
        'browser_data' => 'array',
        'device_data' => 'array',
        'country_data' => 'array',
        'referrer_data' => 'array',
        'retention_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the game associated with these metrics.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }
}