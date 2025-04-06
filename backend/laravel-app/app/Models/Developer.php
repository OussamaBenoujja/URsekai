<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Developer extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'developers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'developer_id';

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
        'user_id',
        'company_name',
        'website',
        'logo_url',
        'description',
        'founding_date',
        'team_size',
        'verified',
        'verification_date',
        'developer_level',
        'total_games_published',
        'total_downloads',
        'payout_email',
        'stripe_account_id',
        'paypal_email',
        'tax_id',
        'developer_agreement_signed',
        'agreement_signed_date',
        'revenue_share_percentage',
        'custom_developer_page',
        'custom_page_theme',
        'api_key',
        'api_key_created',
        'api_key_last_used',
        'webhook_url',
        'webhook_secret'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'bank_account_info',
        'api_key',
        'webhook_secret',
        'stripe_account_id',
        'tax_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'founding_date' => 'date',
        'verification_date' => 'datetime',
        'agreement_signed_date' => 'datetime',
        'api_key_created' => 'datetime',
        'api_key_last_used' => 'datetime',
        'verified' => 'boolean',
        'developer_agreement_signed' => 'boolean',
        'custom_developer_page' => 'boolean',
        'custom_page_theme' => 'array',
        'revenue_share_percentage' => 'float',
    ];

    /**
     * Get the user associated with the developer.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get all games published by this developer.
     */
    public function games()
    {
        return $this->hasMany(Game::class, 'developer_id', 'developer_id');
    }

    /**
     * Get all published games by this developer.
     */
    public function publishedGames()
    {
        return $this->games()->where('is_published', true)
                    ->where('is_approved', true);
    }

    /**
     * Get webhooks for this developer.
     */
    public function webhooks()
    {
        return $this->hasMany(Webhook::class, 'developer_id', 'developer_id');
    }

    /**
     * Get API logs for this developer.
     */
    public function apiLogs()
    {
        return $this->hasMany(ApiLog::class, 'developer_id', 'developer_id');
    }
    
    /**
     * Get users who follow this developer.
     */
    public function followers()
    {
        return $this->hasMany(DeveloperFollower::class, 'developer_id', 'developer_id');
    }
    
    /**
     * Get users who follow this developer with easy access to the User model.
     */
    public function followingUsers()
    {
        return $this->belongsToMany(User::class, 'developer_followers', 'developer_id', 'user_id')
            ->withPivot('notify_new_games', 'notify_updates')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include verified developers.
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Check if developer has published any games.
     */
    public function hasPublishedGames()
    {
        return $this->total_games_published > 0;
    }

    /**
     * Generate a new API key for the developer.
     */
    public function generateApiKey()
    {
        $this->api_key = bin2hex(random_bytes(32));
        $this->api_key_created = now();
        $this->save();

        return $this->api_key;
    }
}