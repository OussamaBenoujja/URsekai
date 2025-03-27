<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Friend;
use App\Models\User;
use App\Models\Notification;
use App\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FriendshipController extends Controller
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
     * Get user's friends list.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status', 'accepted');

        // Find friendships where the user is either the requester or receiver
        $query = Friend::where(function($q) use ($user, $status) {
            $q->where('user_id', $user->user_id)
              ->where('status', $status);
        })->orWhere(function($q) use ($user, $status) {
            $q->where('friend_id', $user->user_id)
              ->where('status', $status);
        });

        $friends = $query->with(['user', 'friend'])->paginate($request->input('per_page', 20));

        // Format the response to include only the friend's information
        $formattedFriends = $friends->getCollection()->map(function($friendship) use ($user) {
            // Determine which user is the friend (not the current user)
            $friendUser = $friendship->user_id == $user->user_id 
                        ? $friendship->friend 
                        : $friendship->user;
            
            return [
                'friendship_id' => $friendship->friendship_id,
                'user_id' => $friendUser->user_id,
                'username' => $friendUser->username,
                'display_name' => $friendUser->display_name,
                'avatar_url' => $friendUser->avatar_url,
                'status' => $friendship->status,
                'requested_at' => $friendship->requested_at,
                'accepted_at' => $friendship->accepted_at,
                'is_online' => isset($friendUser->last_login_date) && 
                               $friendUser->last_login_date > now()->subMinutes(15),
                'notes' => $friendship->notes
            ];
        });

        // Replace the items in the paginator with our formatted collection
        $friends->setCollection($formattedFriends);

        return $this->success($friends);
    }

    /**
     * Get pending friend requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pendingRequests()
    {
        $user = Auth::user();
        
        // Get friend requests sent to the user
        $pendingRequests = Friend::where('friend_id', $user->user_id)
                              ->where('status', 'pending')
                              ->with('user')
                              ->get();

        $formattedRequests = $pendingRequests->map(function($request) {
            return [
                'friendship_id' => $request->friendship_id,
                'user_id' => $request->user->user_id,
                'username' => $request->user->username,
                'display_name' => $request->user->display_name,
                'avatar_url' => $request->user->avatar_url,
                'requested_at' => $request->requested_at
            ];
        });

        return $this->success($formattedRequests);
    }

    /**
     * Get sent friend requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sentRequests()
    {
        $user = Auth::user();
        
        // Get friend requests sent by the user
        $sentRequests = Friend::where('user_id', $user->user_id)
                            ->where('status', 'pending')
                            ->with('friend')
                            ->get();

        $formattedRequests = $sentRequests->map(function($request) {
            return [
                'friendship_id' => $request->friendship_id,
                'user_id' => $request->friend->user_id,
                'username' => $request->friend->username,
                'display_name' => $request->friend->display_name,
                'avatar_url' => $request->friend->avatar_url,
                'requested_at' => $request->requested_at
            ];
        });

        return $this->success($formattedRequests);
    }

    /**
     * Send a friend request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required|integer|exists:users,user_id',
            'notes' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = Auth::user();
        $friendId = $request->friend_id;

        // Check if trying to add self
        if ($user->user_id == $friendId) {
            return $this->error('You cannot add yourself as a friend', 422);
        }

        // Check if friendship already exists
        $existingFriendship = Friend::where(function($q) use ($user, $friendId) {
            $q->where('user_id', $user->user_id)
              ->where('friend_id', $friendId);
        })->orWhere(function($q) use ($user, $friendId) {
            $q->where('user_id', $friendId)
              ->where('friend_id', $user->user_id);
        })->first();

        if ($existingFriendship) {
            if ($existingFriendship->status == 'blocked') {
                return $this->error('Unable to send friend request', 403);
            }
            
            if ($existingFriendship->status == 'pending') {
                return $this->error('Friend request already sent', 422);
            }
            
            if ($existingFriendship->status == 'accepted') {
                return $this->error('You are already friends with this user', 422);
            }
            
            // If declined, allow to send a new request
            if ($existingFriendship->status == 'declined') {
                $existingFriendship->status = 'pending';
                $existingFriendship->requested_at = now();
                $existingFriendship->declined_at = null;
                $existingFriendship->notes = $request->notes;
                $existingFriendship->save();
                
                // Create notification for the friend
                $this->createFriendRequestNotification($user, $friendId);
                
                return $this->success($existingFriendship, 'Friend request sent successfully');
            }
        }

        // Create new friendship
        $friendship = Friend::create([
            'user_id' => $user->user_id,
            'friend_id' => $friendId,
            'status' => 'pending',
            'requested_at' => now(),
            'notes' => $request->notes
        ]);

        // Create notification for the friend
        $this->createFriendRequestNotification($user, $friendId);

        return $this->success($friendship, 'Friend request sent successfully');
    }

    /**
     * Accept a friend request.
     *
     * @param int $friendshipId
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptRequest($friendshipId)
    {
        $user = Auth::user();
        
        $friendship = Friend::where('friendship_id', $friendshipId)
                          ->where('friend_id', $user->user_id)
                          ->where('status', 'pending')
                          ->firstOrFail();

        $friendship->status = 'accepted';
        $friendship->accepted_at = now();
        $friendship->save();

        // Create notification for the original requester
        $this->createFriendRequestAcceptedNotification($user, $friendship->user_id);

        return $this->success($friendship, 'Friend request accepted successfully');
    }

    /**
     * Decline a friend request.
     *
     * @param int $friendshipId
     * @return \Illuminate\Http\JsonResponse
     */
    public function declineRequest($friendshipId)
    {
        $user = Auth::user();
        
        $friendship = Friend::where('friendship_id', $friendshipId)
                          ->where('friend_id', $user->user_id)
                          ->where('status', 'pending')
                          ->firstOrFail();

        $friendship->status = 'declined';
        $friendship->declined_at = now();
        $friendship->save();

        return $this->success($friendship, 'Friend request declined');
    }

    /**
     * Block a user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function blockUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,user_id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = Auth::user();
        $blockUserId = $request->user_id;

        // Check if trying to block self
        if ($user->user_id == $blockUserId) {
            return $this->error('You cannot block yourself', 422);
        }

        // Check if there's an existing relationship
        $existingFriendship = Friend::where(function($q) use ($user, $blockUserId) {
            $q->where('user_id', $user->user_id)
              ->where('friend_id', $blockUserId);
        })->orWhere(function($q) use ($user, $blockUserId) {
            $q->where('user_id', $blockUserId)
              ->where('friend_id', $user->user_id);
        })->first();

        if ($existingFriendship) {
            // Update existing relationship to blocked
            $existingFriendship->status = 'blocked';
            $existingFriendship->blocked_at = now();
            
            // Ensure the blocking user is the 'user_id'
            if ($existingFriendship->friend_id == $user->user_id) {
                // Swap the user_id and friend_id
                $temp = $existingFriendship->user_id;
                $existingFriendship->user_id = $existingFriendship->friend_id;
                $existingFriendship->friend_id = $temp;
            }
            
            $existingFriendship->save();
            
            return $this->success($existingFriendship, 'User blocked successfully');
        }

        // Create new block relationship
        $friendship = Friend::create([
            'user_id' => $user->user_id,
            'friend_id' => $blockUserId,
            'status' => 'blocked',
            'requested_at' => now(),
            'blocked_at' => now()
        ]);

        return $this->success($friendship, 'User blocked successfully');
    }

    /**
     * Unblock a user.
     *
     * @param int $friendshipId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unblockUser($friendshipId)
    {
        $user = Auth::user();
        
        $friendship = Friend::where('friendship_id', $friendshipId)
                          ->where('user_id', $user->user_id)
                          ->where('status', 'blocked')
                          ->firstOrFail();

        // Delete the relationship
        $friendship->delete();

        return $this->success(null, 'User unblocked successfully');
    }

    /**
     * Remove a friend.
     *
     * @param int $friendshipId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFriend($friendshipId)
    {
        $user = Auth::user();
        
        $friendship = Friend::where('friendship_id', $friendshipId)
                          ->where(function($q) use ($user) {
                              $q->where('user_id', $user->user_id)
                                ->orWhere('friend_id', $user->user_id);
                          })
                          ->where('status', 'accepted')
                          ->firstOrFail();

        // Delete the relationship
        $friendship->delete();

        return $this->success(null, 'Friend removed successfully');
    }

    /**
     * Get blocked users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function blockedUsers()
    {
        $user = Auth::user();
        
        $blockedUsers = Friend::where('user_id', $user->user_id)
                            ->where('status', 'blocked')
                            ->with('friend')
                            ->get();

        $formattedUsers = $blockedUsers->map(function($block) {
            return [
                'friendship_id' => $block->friendship_id,
                'user_id' => $block->friend->user_id,
                'username' => $block->friend->username,
                'display_name' => $block->friend->display_name,
                'avatar_url' => $block->friend->avatar_url,
                'blocked_at' => $block->blocked_at
            ];
        });

        return $this->success($formattedUsers);
    }

    /**
     * Search for users to add as friends.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = Auth::user();
        $search = $request->search;

        // Get users that match the search term
        $users = User::where('user_id', '!=', $user->user_id)
                    ->where(function($q) use ($search) {
                        $q->where('username', 'LIKE', "%$search%")
                          ->orWhere('display_name', 'LIKE', "%$search%");
                    })
                    ->take(20)
                    ->get(['user_id', 'username', 'display_name', 'avatar_url']);

        // Get friendship status if any
        $userIds = $users->pluck('user_id')->toArray();
        $friendships = Friend::where(function($q) use ($user, $userIds) {
                            $q->where('user_id', $user->user_id)
                              ->whereIn('friend_id', $userIds);
                        })->orWhere(function($q) use ($user, $userIds) {
                            $q->whereIn('user_id', $userIds)
                              ->where('friend_id', $user->user_id);
                        })->get();

        // Format results with friendship status
        $results = $users->map(function($searchedUser) use ($friendships, $user) {
            $friendship = $friendships->first(function($f) use ($searchedUser, $user) {
                return ($f->user_id == $user->user_id && $f->friend_id == $searchedUser->user_id) ||
                       ($f->user_id == $searchedUser->user_id && $f->friend_id == $user->user_id);
            });

            $status = null;
            $friendshipId = null;
            $requestSent = false;

            if ($friendship) {
                $status = $friendship->status;
                $friendshipId = $friendship->friendship_id;
                $requestSent = $friendship->status == 'pending' && $friendship->user_id == $user->user_id;
            }

            return [
                'user_id' => $searchedUser->user_id,
                'username' => $searchedUser->username,
                'display_name' => $searchedUser->display_name,
                'avatar_url' => $searchedUser->avatar_url,
                'friendship_status' => $status,
                'friendship_id' => $friendshipId,
                'request_sent' => $requestSent,
            ];
        });

        return $this->success($results);
    }

    /**
     * Create friend request notification.
     *
     * @param User $sender
     * @param int $recipientId
     * @return void
     */
    private function createFriendRequestNotification($sender, $recipientId)
    {
        Notification::create([
            'user_id' => $recipientId,
            'type' => 'friend_request',
            'title' => 'New Friend Request',
            'message' => "{$sender->display_name} has sent you a friend request",
            'is_read' => false,
            'created_at' => now(),
            'priority' => 'normal',
            'link' => '/profile/friends/requests',
            'related_id' => $sender->user_id,
            'related_type' => 'user'
        ]);
    }

    /**
     * Create friend request accepted notification.
     *
     * @param User $accepter
     * @param int $requesterId
     * @return void
     */
    private function createFriendRequestAcceptedNotification($accepter, $requesterId)
    {
        Notification::create([
            'user_id' => $requesterId,
            'type' => 'friend_request_accepted',
            'title' => 'Friend Request Accepted',
            'message' => "{$accepter->display_name} has accepted your friend request",
            'is_read' => false,
            'created_at' => now(),
            'priority' => 'normal',
            'link' => '/profile/friends',
            'related_id' => $accepter->user_id,
            'related_type' => 'user'
        ]);
    }
}