<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Notification;
use App\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    use ApiResponser;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get the authenticated user's profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        $user = Auth::user();
        return $this->success($user);
    }
    
    /**
     * Get a user's public profile by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        
        // Return only public information
        return $this->success([
            'id' => $user->id,
            'display_name' => $user->display_name ?? $user->name,
            'avatar_url' => $user->avatar_url,
            'bio' => $user->bio,
            'country' => $user->country,
            'account_level' => $user->account_level,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * Update the user's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'display_name' => 'sometimes|string|max:50',
            'bio' => 'sometimes|string|max:500',
            'country' => 'sometimes|string|max:50',
            'city' => 'sometimes|string|max:50',
            'theme_preference' => 'sometimes|in:light,dark,system',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }
        
        // Update fields
        if ($request->has('display_name')) {
            $user->display_name = $request->display_name;
        }
        
        if ($request->has('bio')) {
            $user->bio = $request->bio;
        }
        
        if ($request->has('country')) {
            $user->country = $request->country;
        }
        
        if ($request->has('city')) {
            $user->city = $request->city;
        }
        
        if ($request->has('theme_preference')) {
            $user->theme_preference = $request->theme_preference;
        }
        
        $user->save();
        
        return $this->success($user, 'Profile updated successfully');
    }

    /**
     * Update the user's avatar.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }
        
        $user = Auth::user();
        
        // Delete old avatar if exists
        if ($user->avatar_url && !str_contains($user->avatar_url, 'default')) {
            $oldAvatarPath = str_replace('/storage/', '', $user->avatar_url);
            if (Storage::disk('public')->exists($oldAvatarPath)) {
                Storage::disk('public')->delete($oldAvatarPath);
            }
        }
        
        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar_url = '/storage/' . $path;
        $user->save();
        
        return $this->success(['avatar_url' => $user->avatar_url], 'Avatar updated successfully');
    }

    /**
     * Update the user's password.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }
        
        $user = Auth::user();
        
        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect', 422);
        }
        
        // Update password
        $user->password = Hash::make($request->password);
        $user->save();
        
        return $this->success(null, 'Password updated successfully');
    }

    /**
     * Update privacy settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePrivacySettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'privacy_settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }
        
        $user = Auth::user();
        $user->privacy_settings = $request->privacy_settings;
        $user->save();
        
        return $this->success($user->privacy_settings, 'Privacy settings updated successfully');
    }
/**
 * Get all notifications for the authenticated user.
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function notifications(Request $request)
{
    $user = Auth::user();
    
    $query = Notification::where('user_id', $user->user_id);
    
    // Filter by read status if specified
    if ($request->has('is_read')) {
        $query->where('is_read', (bool)$request->is_read);
    }
    
    // Filter by type if specified
    if ($request->has('type')) {
        $query->where('type', $request->type);
    }
    
    // Filter by priority if specified
    if ($request->has('priority')) {
        $query->where('priority', $request->priority);
    }
    
    // Order by priority (high to low) and then by date (newest first)
    $notifications = $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
                          ->orderBy('created_at', 'desc')
                          ->paginate($request->input('per_page', 15));
    
    return $this->success($notifications);
}

/**
 * Get count of unread notifications for the authenticated user.
 *
 * @return \Illuminate\Http\JsonResponse
 */
public function unreadNotificationsCount()
{
    $user = Auth::user();
    
    $count = Notification::where('user_id', $user->user_id)
                        ->where('is_read', false)
                        ->count();
    
    return $this->success(['count' => $count]);
}

/**
 * Mark a notification as read.
 *
 * @param int $id
 * @return \Illuminate\Http\JsonResponse
 */
public function markNotificationAsRead($id)
{
    $user = Auth::user();
    
    $notification = Notification::where('notification_id', $id)
                                ->where('user_id', $user->user_id)
                                ->firstOrFail();
    
    $notification->is_read = true;
    $notification->read_at = now();
    $notification->save();
    
    return $this->success($notification, 'Notification marked as read');
}

/**
 * Mark all notifications as read.
 *
 * @return \Illuminate\Http\JsonResponse
 */
public function markAllNotificationsAsRead()
{
    $user = Auth::user();
    
    Notification::where('user_id', $user->user_id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
    
    return $this->success(null, 'All notifications marked as read');
}

/**
 * Delete a notification.
 *
 * @param int $id
 * @return \Illuminate\Http\JsonResponse
 */
public function deleteNotification($id)
{
    $user = Auth::user();
    
    $notification = Notification::where('notification_id', $id)
                                ->where('user_id', $user->user_id)
                                ->firstOrFail();
    
    $notification->delete();
    
    return $this->success(null, 'Notification deleted successfully');
}

/**
 * Update notification preferences.
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function updateNotificationPreferences(Request $request)
{
    $validator = Validator::make($request->all(), [
        'notification_preferences' => 'required|array',
    ]);

    if ($validator->fails()) {
        return $this->error('Validation failed', 422, $validator->errors());
    }
    
    $user = Auth::user();
    $user->notification_preferences = $request->notification_preferences;
    $user->save();
    
    return $this->success($user->notification_preferences, 'Notification preferences updated successfully');
}

/**
 * Get all friendships where this user is the requester.
 */
public function sentFriendships()
{
    return $this->hasMany(Friend::class, 'user_id', 'user_id');
}

/**
 * Get all friendships where this user is the receiver.
 */
public function receivedFriendships()
{
    return $this->hasMany(Friend::class, 'friend_id', 'user_id');
}

/**
 * Get all friends (accepted friendships)
 */
public function friends()
{
    return $this->sentFriendships()->where('status', 'accepted')
        ->with('friend')
        ->get()
        ->map(function ($friendship) {
            return $friendship->friend;
        })
        ->merge(
            $this->receivedFriendships()->where('status', 'accepted')
                ->with('user')
                ->get()
                ->map(function ($friendship) {
                    return $friendship->user;
                })
        );
}

/**
 * Check if user is friends with another user
 * 
 * @param int $userId
 * @return bool
 */
public function isFriendsWith($userId)
{
    return $this->sentFriendships()
        ->where('friend_id', $userId)
        ->where('status', 'accepted')
        ->exists() || 
        $this->receivedFriendships()
        ->where('user_id', $userId)
        ->where('status', 'accepted')
        ->exists();
}

/**
 * Check if user has a pending friend request from another user
 * 
 * @param int $userId
 * @return bool
 */
public function hasPendingFriendRequestFrom($userId)
{
    return $this->receivedFriendships()
        ->where('user_id', $userId)
        ->where('status', 'pending')
        ->exists();
}

/**
 * Check if user has sent a pending friend request to another user
 * 
 * @param int $userId
 * @return bool
 */
public function hasSentPendingFriendRequestTo($userId)
{
    return $this->sentFriendships()
        ->where('friend_id', $userId)
        ->where('status', 'pending')
        ->exists();
}

/**
 * Check if user has blocked another user
 * 
 * @param int $userId
 * @return bool
 */
public function hasBlocked($userId)
{
    return $this->sentFriendships()
        ->where('friend_id', $userId)
        ->where('status', 'blocked')
        ->exists();
}

/**
 * Check if user is blocked by another user
 * 
 * @param int $userId
 * @return bool
 */
public function isBlockedBy($userId)
{
    return $this->receivedFriendships()
        ->where('user_id', $userId)
        ->where('status', 'blocked')
        ->exists();
}
}