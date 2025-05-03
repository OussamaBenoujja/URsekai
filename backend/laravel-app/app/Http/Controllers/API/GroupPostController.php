<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\GroupPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\ApiResponser;

class GroupPostController extends Controller
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request, $groupId)
    {
        $group = \App\Models\Group::findOrFail($groupId);

        if (!$group->is_public) {
            // Only allow joined members to see posts
            $userId = auth()->id();
            $isMember = \DB::table('group_members')
                ->where('group_id', $groupId)
                ->where('user_id', $userId)
                ->exists();
            if (!$isMember) {
                return response()->json(['message' => 'You must join this group to view posts.'], 403);
            }
        }

        // List posts in group (with user and comments)
        $posts = GroupPost::with(['user', 'comments.user'])
            ->where('group_id', $groupId)
            ->whereNull('parent_post_id')
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json($posts);
    }

    /**
     * Store a new group post (text, image, or video).
     */
    public function store(Request $request, $groupId)
    {
        $userId = auth('api')->id();
        $group = \App\Models\Group::findOrFail($groupId);

        // Only allow posting if user is a member
        $isMember = \DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->exists();
        if (!$isMember) {
            return response()->json(['message' => 'Join the group to post.'], 403);
        }

        $user = Auth::user();
        $validated = $request->validate([
            'content' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpeg,png,gif,webp,mp4,webm,ogg|max:20480', // 20MB max
        ]);

        $attachmentUrl = null;
        $attachmentType = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $mime = $file->getMimeType();
            if (str_starts_with($mime, 'image/')) {
                $attachmentType = 'image';
                $path = $file->store('groups/posts/images', 'public');
            } elseif (str_starts_with($mime, 'video/')) {
                $attachmentType = 'video';
                $path = $file->store('groups/posts/videos', 'public');
            } else {
                return $this->error('Unsupported file type', 422);
            }
            $attachmentUrl = '/storage/' . $path;
        }

        $postId = DB::table('group_posts')->insertGetId([
            'group_id' => $groupId,
            'user_id' => $user->user_id,
            'content' => $validated['content'] ?? '',
            'attachment_url' => $attachmentUrl,
            'attachment_type' => $attachmentType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['post_id' => $postId], 'Post created');
    }

    // For likes, allow all users if group is public, else only members
    public function like(Request $request, $postId)
    {
        $userId = auth('api')->id();
        $post = \App\Models\GroupPost::findOrFail($postId);
        $group = \App\Models\Group::findOrFail($post->group_id);

        $isMember = \DB::table('group_members')
            ->where('group_id', $group->group_id)
            ->where('user_id', $userId)
            ->exists();

        if (!$group->is_public && !$isMember) {
            return response()->json(['message' => 'Join the group to like posts.'], 403);
        }

        // TODO: Implement like logic (e.g., group_post_likes table)
        return response()->json(['message' => 'Liked post (not implemented)']);
    }

    public function unlike($post) {
        // TODO: Implement unlike logic
        return response()->json(['message' => 'Unliked post (not implemented)']);
    }

    // For replies, allow all authenticated users to reply (if group is public or user is a member)
    public function comment(Request $request, $postId)
    {
        $userId = auth('api')->id();
        $post = \App\Models\GroupPost::findOrFail($postId);
        $group = \App\Models\Group::findOrFail($post->group_id);

        $isMember = \DB::table('group_members')
            ->where('group_id', $group->group_id)
            ->where('user_id', $userId)
            ->exists();

        if (!$group->is_public && !$isMember) {
            return response()->json(['message' => 'Join the group to reply.'], 403);
        }

        $data = $request->validate([
            'content' => 'required|string',
        ]);
        $parent = GroupPost::findOrFail($postId);
        $comment = GroupPost::create([
            'group_id' => $parent->group_id,
            'user_id' => $request->user()->user_id,
            'parent_post_id' => $parent->post_id,
            'content' => $data['content'],
        ]);
        // TODO: Trigger notification to post owner
        return response()->json($comment, 201);
    }

    public function deleteComment($post, $comment) {
        $comment = GroupPost::where('post_id', $comment)
            ->where('parent_post_id', $post)
            ->where('user_id', auth()->user()->user_id)
            ->firstOrFail();
        $comment->delete();
        return response()->json(['message' => 'Comment deleted']);
    }
}
