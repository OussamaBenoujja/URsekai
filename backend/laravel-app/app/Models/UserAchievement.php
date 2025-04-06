<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAchievement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_achievements';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_achievement_id';

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
        'achievement_id',
        'unlocked_at',
        'game_state_data'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unlocked_at' => 'datetime',
        'game_state_data' => 'array',
    ];

    /**
     * Get the user that owns this achievement record.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the achievement that this record refers to.
     */
    public function achievement()
    {
        return $this->belongsTo(Achievement::class, 'achievement_id', 'achievement_id');
    }
}