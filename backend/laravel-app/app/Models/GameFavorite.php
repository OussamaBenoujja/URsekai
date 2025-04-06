<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameFavorite extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'game_favorites';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'favorite_id';

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
        'created_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who favorited the game.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the game that was favorited.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }
}