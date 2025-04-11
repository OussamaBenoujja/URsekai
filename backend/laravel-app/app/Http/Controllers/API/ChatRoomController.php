        \Log::info('Broadcasting ChatMessageSent', ['roomId' => $roomId, 'messageId' => $messageId, 'userId' => $userId]);
        // Broadcast the message via Reverb
        broadcast(new ChatMessageSent($roomId, $message))->toOthers();
        \Log::info('Broadcasted ChatMessageSent', ['roomId' => $roomId, 'messageId' => $messageId]);
        return response()->json($message, 201);
    }
}
