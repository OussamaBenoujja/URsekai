                'link' => '/messages',
                'related_id' => $user->user_id,
                'related_type' => 'user'
            ]
        );

        return response()->json(['message' => 'Message sent', 'data' => $message]);
    }
}
