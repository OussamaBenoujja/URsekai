<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ForumPost;
use App\Models\ForumThread;

class ForumPostController extends Controller
{
    public function reply(Request $request, $thread) {
        $data = $request->validate([
            'content' => 'required|string',
            'parent_post_id' => 'nullable|integer',
        ]);
        $thread = ForumThread::findOrFail($thread);
        $post = ForumPost::create([
            'thread_id' => $thread->thread_id,
            'user_id' => $request->user()->user_id,
            'content' => $data['content'],
            'parent_post_id' => $data['parent_post_id'] ?? null,
        ]);
        // TODO: Trigger notification to thread owner or parent post owner
        return response()->json($post, 201);
    }
    public function upvote($post) {
        // TODO: Implement upvote logic (forum_post_votes table)
        return response()->json(['message' => 'Upvoted post (not implemented)']);
    }
    public function downvote($post) {
        // TODO: Implement downvote logic
        return response()->json(['message' => 'Downvoted post (not implemented)']);
    }
    public function destroy($post) {
        $post = ForumPost::where('post_id', $post)
            ->where('user_id', auth()->user()->user_id)
            ->firstOrFail();
        $post->delete();
        return response()->json(['message' => 'Post deleted']);
    }
    public function replyToReply(Request $request, $post) {
        $parent = ForumPost::findOrFail($post);
        $data = $request->validate([
            'content' => 'required|string',
        ]);
        $reply = ForumPost::create([
            'thread_id' => $parent->thread_id,
            'user_id' => $request->user()->user_id,
            'content' => $data['content'],
            'parent_post_id' => $parent->post_id,
        ]);
        // TODO: Trigger notification to parent post owner
        return response()->json($reply, 201);
    }
}
