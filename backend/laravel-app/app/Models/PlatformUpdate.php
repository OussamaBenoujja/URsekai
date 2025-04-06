<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformUpdate extends Model
{
    protected $table = 'platform_updates';
    protected $primaryKey = 'update_id';
    public $timestamps = false;
    protected $fillable = [
        'title',
        'content',
        'image_url',
        'created_by',
        'created_at',
        'updated_at',
        'is_active',
    ];

    // Optionally, add relationship to User
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
