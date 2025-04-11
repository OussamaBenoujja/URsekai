    {
        $participants = DB::table('voice_call_participants')
            ->join('users', 'voice_call_participants.user_id', '=', 'users.user_id')
            ->where('call_id', $callId)
            ->whereNull('left_at')
            ->select('users.user_id', 'users.username', 'users.avatar_url', 'voice_call_participants.joined_at')
            ->get();
        return response()->json($participants);
    }

    // WebRTC signaling endpoint
    public function signal(Request $request, $callId)
    {
        $userId = Auth::id();
        $request->validate([
            'signal' => 'required|array',
        ]);
        broadcast(new VoiceCallSignal($callId, $userId, $request->input('signal')))->toOthers();
        return response()->json(['success' => true]);
    }
}
