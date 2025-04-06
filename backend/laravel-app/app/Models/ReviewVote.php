<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewVote extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'review_votes';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'vote_id';

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
        'review_id',
        'user_id',
        'vote_type',
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
     * Get the review that was voted on.
     */
    public function review()
    {
        return $this->belongsTo(GameReview::class, 'review_id', 'review_id');
    }

    /**
     * Get the user who cast the vote.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}