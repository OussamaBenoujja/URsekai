<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

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
        'username',
        'email',
        'password',
        'password_hash',
        'display_name',
        'avatar_url',
        'bio',
        'country',
        'city',
        'date_of_birth',
        'theme_preference',
        'account_level',
        'experience_points',
        'total_playtime_minutes',
        'notification_preferences',
        'privacy_settings',
        'registration_date',
        'last_login_date',
        'is_active',
        'role',
        'preferred_language',
        'two_factor_secret', // Add 2FA secret
        'two_factor_enabled', // Add 2FA enabled status
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'password_hash',
        'remember_token',
        'two_factor_secret', // Hide 2FA secret
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'registration_date' => 'datetime',
            'last_login_date' => 'datetime',
            'notification_preferences' => 'array',
            'privacy_settings' => 'array',
            'date_of_birth' => 'date',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getAttribute($this->primaryKey);
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get the achievements unlocked by the user.
     */
    public function achievements()
    {
        return $this->belongsToMany(
            Achievement::class,
            'user_achievements',
            'user_id',
            'achievement_id'
        )->withPivot('unlocked_at', 'game_state_data');
    }

    /**
     * Get the badges earned by the user.
     */
    public function badges()
    {
        return $this->belongsToMany(
            Badge::class,
            'user_badges',
            'user_id',
            'badge_id'
        )->withPivot('awarded_at', 'awarded_by', 'is_featured');
    }

    /**
     * Get all friends (accepted friendships)
     */
    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted');
    }

    /**
     * Users this user has blocked
     */
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'user_id', 'blocked_user_id');
    }

    /**
     * Users who have blocked this user
     */
    public function blockedByUsers()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'blocked_user_id', 'user_id');
    }

    /**
     * Check if this user has blocked another user
     */
    public function hasBlocked($otherUserId)
    {
        return $this->blockedUsers()->where('blocked_users.blocked_user_id', $otherUserId)->exists();
    }

    /**
     * Check if this user is blocked by another user
     */
    public function isBlockedBy($otherUserId)
    {
        return $this->blockedByUsers()->where('blocked_users.user_id', $otherUserId)->exists();
    }

    /**
     * Check if this user is friends with another user
     */
    public function isFriendsWith($otherUserId)
    {
        return \DB::table('friends')
            ->where(function($q) use ($otherUserId) {
                $q->where(function($q2) use ($otherUserId) {
                    $q2->where('user_id', $this->user_id)
                       ->where('friend_id', $otherUserId);
                })->orWhere(function($q2) use ($otherUserId) {
                    $q2->where('user_id', $otherUserId)
                       ->where('friend_id', $this->user_id);
                });
            })
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * Check if this user has sent a pending friend request to another user
     */
    public function hasSentPendingFriendRequestTo($otherUserId)
    {
        return \DB::table('friends')
            ->where('user_id', $this->user_id)
            ->where('friend_id', $otherUserId)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Check if this user has a pending friend request from another user
     */
    public function hasPendingFriendRequestFrom($otherUserId)
    {
        return \DB::table('friends')
            ->where('user_id', $otherUserId)
            ->where('friend_id', $this->user_id)
            ->where('status', 'pending')
            ->exists();
    }
}