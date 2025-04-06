<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forum extends Model
{
    use HasFactory;
    protected $table = 'forums';
    protected $primaryKey = 'forum_id';
    protected $fillable = [
        'name', 'description', 'slug', 'icon_url', 'display_order', 'parent_forum_id',
        'is_active', 'is_private', 'required_role', 'created_at', 'updated_at',
        'total_threads', 'total_posts', 'last_post_id'
    ];

    public function threads() {
        return $this->hasMany(ForumThread::class, 'forum_id', 'forum_id');
    }
}
