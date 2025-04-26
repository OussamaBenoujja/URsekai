        $data['slug'] = \Str::slug($data['title']) . '-' . uniqid();
        $thread = ForumThread::create($data);
        // TODO: Trigger notification to forum subscribers
        return response()->json($thread, 201);
    }
    public function show($thread) {
        $thread = ForumThread::with(['user', 'posts.user'])->findOrFail($thread);
        return response()->json($thread);
    }
}
