<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeveloperFollower extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'developer_followers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'follower_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'developer_id',
        'user_id',
        'notify_new_games',
        'notify_updates',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'notify_new_games' => 'boolean',
        'notify_updates' => 'boolean',
    ];

    /**
     * Get the developer that the user is following.
     */
    public function developer()
    {
        return $this->belongsTo(Developer::class, 'developer_id', 'developer_id');
    }

    /**
     * Get the user that is following the developer.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}