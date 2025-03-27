<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponser;
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
}