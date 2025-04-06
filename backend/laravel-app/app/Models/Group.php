<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    protected $table = 'groups';
    protected $primaryKey = 'group_id';
    protected $fillable = [
        'name', 'slug', 'description', 'logo_url', 'banner_url', 'created_by', 'group_type', 'game_id', 'developer_id', 'rules', 'discord_url', 'website_url', 'custom_css', 'is_active'
    ];

    public function posts() {
        return $this->hasMany(GroupPost::class, 'group_id', 'group_id');
    }
    public function creator() {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
    public function members()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'group_members',
            'group_id',
            'user_id'
        );
    }
}
