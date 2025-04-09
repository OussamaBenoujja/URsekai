    // Example using Laravel Echo with Reverb
    if (typeof Echo === 'undefined') {
        console.warn('Echo is not defined!');
        return;
    }
    console.log('Connecting to chat rooms for real-time updates...');
    fetch(API_BASE + '/chat-rooms', {
        headers: getAuthHeaders()
    })
        .then(res => res.json())
        .then(rooms => {
            rooms.forEach(room => {
                console.log('Subscribing to channel:', 'chat-room.' + room.room_id);
                Echo.channel('chat-room.' + room.room_id)
                    .listen('ChatMessageSent', (e) => {
                        console.log('Received ChatMessageSent event:', e);
                        handleIncomingMessage(e.message);
                    });
            });
        });
    // Subscribe to all active voice calls (for demo, subscribe to a generic channel)
    // In production, subscribe to voice-call.{call_id} when joining/starting a call
    if (currentCallId) {
        Echo.channel('voice-call.' + currentCallId)
            .listen('VoiceCallStarted', (e) => { console.log('VoiceCallStarted', e); })
