<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoiceCallEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $callId;
    public $endedBy;

    public function __construct($callId, $endedBy)
    {
        $this->callId = $callId;
        $this->endedBy = $endedBy;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('voice-call.' . $this->callId);
    }

    public function broadcastWith()
    {
        return [
            'call_id' => $this->callId,
            'ended_by' => $this->endedBy,
        ];
    }
}
