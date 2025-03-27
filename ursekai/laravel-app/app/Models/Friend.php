<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'friends';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'friendship_id';

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
        'friend_id',
        'status',
        'requested_at',
        'accepted_at',
        'declined_at',
        'blocked_at',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    /**
     * Get the user who initiated the friendship.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the friend user.
     */
    public function friend()
    {
        return $this->belongsTo(User::class, 'friend_id', 'user_id');
    }
}