<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoiceCallStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $callId;
    public $roomId;
    public $initiatorId;

    public function __construct($callId, $roomId, $initiatorId)
    {
        $this->callId = $callId;
        $this->roomId = $roomId;
        $this->initiatorId = $initiatorId;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('voice-call.' . $this->callId);
    }

    public function broadcastWith()
    {
        return [
            'call_id' => $this->callId,
            'room_id' => $this->roomId,
            'initiator_id' => $this->initiatorId,
        ];
    }
}
