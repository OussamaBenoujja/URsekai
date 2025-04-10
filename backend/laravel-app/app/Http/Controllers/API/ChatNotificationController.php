                'user_mentioned_chat',
                "You were mentioned by {$mentioner->display_name} in a chat message.",
                'Mentioned in Chat',
                [
                    'link' => "/chat/room/{$message->room_id}",
                    'related_id' => $messageId,
                    'related_type' => 'chat_message'
                ]
            );
        }
    }
}
