<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Forum;

class ForumController extends Controller
{
    public function index(Request $request) {
        // List all forums
        $forums = Forum::where('is_active', true)->orderBy('display_order')->get();
        return response()->json($forums);
    }
    public function show($forum) {
        $forum = Forum::with(['threads.user'])->findOrFail($forum);
        return response()->json($forum);
    }
}
