<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumPost extends Model
{
    use HasFactory;
    protected $table = 'forum_posts';
    protected $primaryKey = 'post_id';
    protected $fillable = [
        'thread_id', 'user_id', 'content', 'is_solution', 'is_edited', 'edited_at', 'edited_by', 'is_hidden', 'hide_reason', 'hidden_by', 'upvotes', 'downvotes', 'created_at', 'updated_at', 'ip_address', 'parent_post_id'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
    public function thread() {
        return $this->belongsTo(ForumThread::class, 'thread_id', 'thread_id');
    }
    public function replies() {
        return $this->hasMany(ForumPost::class, 'parent_post_id', 'post_id');
    }
    public function parent() {
        return $this->belongsTo(ForumPost::class, 'parent_post_id', 'post_id');
    }
}
