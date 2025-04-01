<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_settings';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'setting_id';

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
        'category',
        'name',
        'value',
        'data_type',
        'description',
        'is_public',
        'created_at',
        'updated_at',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who last updated this setting.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }

    /**
     * Get typed value based on data_type.
     */
    public function getTypedValueAttribute()
    {
        switch ($this->data_type) {
            case 'integer':
                return (int) $this->value;
            case 'float':
                return (float) $this->value;
            case 'boolean':
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($this->value, true);
            case 'date':
                return \Carbon\Carbon::parse($this->value)->toDate();
            case 'datetime':
                return \Carbon\Carbon::parse($this->value);
            default:
                return $this->value;
        }
    }

    /**
     * Scope a query to only include public settings.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeInCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get a setting value by name and category.
     */
    public static function getValue($category, $name, $default = null)
    {
        $setting = self::where('category', $category)
                      ->where('name', $name)
                      ->first();
                      
        if (!$setting) {
            return $default;
        }
        
        return $setting->typed_value;
    }
}