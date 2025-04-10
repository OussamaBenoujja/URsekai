            .listen('VoiceCallJoined', (e) => { console.log('VoiceCallJoined', e); })
            .listen('VoiceCallLeft', (e) => { console.log('VoiceCallLeft', e); })
            .listen('VoiceCallEnded', (e) => { console.log('VoiceCallEnded', e); })
            .listen('VoiceCallSignal', (e) => { console.log('VoiceCallSignal', e); handleWebRTCSignal(e.signal); });
    }
}

function handleWebRTCSignal(signal) {
    // TODO: Pass signal to WebRTC peer connection
}

// --- UI HELPERS ---
// TODO: Add helpers for rendering messages, chat lists, participants, etc.
