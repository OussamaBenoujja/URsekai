<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupPost extends Model
{
    use HasFactory;
    protected $table = 'group_posts';
    protected $primaryKey = 'post_id';
    protected $fillable = [
        'group_id', 'user_id', 'parent_post_id', 'content', 'attachment_url', 'attachment_type', 'is_pinned', 'is_announcement', 'is_hidden', 'hide_reason', 'hidden_by'
    ];

    public function group() {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
    public function comments() {
        return $this->hasMany(GroupPost::class, 'parent_post_id', 'post_id');
    }
    public function parent() {
        return $this->belongsTo(GroupPost::class, 'parent_post_id', 'post_id');
    }
}
