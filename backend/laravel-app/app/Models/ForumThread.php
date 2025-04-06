<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumThread extends Model
{
    use HasFactory;
    protected $table = 'forum_threads';
    protected $primaryKey = 'thread_id';
    protected $fillable = [
        'forum_id', 'user_id', 'game_id', 'title', 'slug', 'content', 'is_sticky', 'is_locked', 'is_hidden', 'hide_reason', 'views', 'upvotes', 'downvotes', 'created_at', 'updated_at', 'last_post_at', 'last_post_id', 'last_poster_id', 'total_posts'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
    public function posts() {
        return $this->hasMany(ForumPost::class, 'thread_id', 'thread_id');
    }
}
