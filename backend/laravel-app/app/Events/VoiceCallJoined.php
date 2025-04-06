<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoiceCallJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $callId;
    public $userId;

    public function __construct($callId, $userId)
    {
        $this->callId = $callId;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('voice-call.' . $this->callId);
    }

    public function broadcastWith()
    {
        return [
            'call_id' => $this->callId,
            'user_id' => $this->userId,
        ];
    }
}
