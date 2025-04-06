<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    protected $table = 'badges';
    protected $primaryKey = 'badge_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'icon_url',
        'category',
        'points',
        'prerequisite_badges',
        'unlock_criteria',
        'is_hidden',
        'is_limited_time',
        'available_from',
        'available_until',
        'total_awarded',
        'max_awards',
        'created_at',
        'updated_at',
        'is_active',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'is_limited_time' => 'boolean',
        'is_active' => 'boolean',
        'points' => 'integer',
        'total_awarded' => 'integer',
        'max_awards' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'prerequisite_badges' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_badges',
            'badge_id',
            'user_id'
        )->withPivot('awarded_at', 'awarded_by', 'is_featured');
    }
}
