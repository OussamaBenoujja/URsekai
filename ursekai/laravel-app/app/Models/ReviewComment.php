<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewComment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'review_comments';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'comment_id';

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
        'parent_comment_id',
        'content',
        'upvotes',
        'downvotes',
        'is_hidden',
        'hide_reason',
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the review that this comment belongs to.
     */
    public function review()
    {
        return $this->belongsTo(GameReview::class, 'review_id', 'review_id');
    }

    /**
     * Get the user who wrote the comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the parent comment if this is a reply.
     */
    public function parentComment()
    {
        return $this->belongsTo(ReviewComment::class, 'parent_comment_id', 'comment_id');
    }

    /**
     * Get replies to this comment.
     */
    public function replies()
    {
        return $this->hasMany(ReviewComment::class, 'parent_comment_id', 'comment_id');
    }

    /**
     * Scope a query to only include non-hidden comments.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }
}