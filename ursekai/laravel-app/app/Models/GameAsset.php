<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameAsset extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'game_assets';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'asset_id';

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
        'game_id',
        'asset_type',
        'file_name',
        'file_path',
        'file_size_bytes',
        'file_extension',
        'mime_type',
        'checksum',
        'version',
        'uploaded_at',
        'is_compressed',
        'width',
        'height',
        'duration',
        'is_active',
        'cdn_url',
        'metadata'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'uploaded_at' => 'datetime',
        'is_compressed' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the game that owns the asset.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    /**
     * Scope a query to only include active assets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include assets of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('asset_type', $type);
    }

    /**
     * Get file size in human-readable format.
     */
    public function getFileSizeAttribute()
    {
        $bytes = $this->file_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the full URL to the asset.
     */
    public function getUrlAttribute()
    {
        if ($this->cdn_url) {
            return $this->cdn_url;
        }

        return url($this->file_path);
    }
}