<?php

namespace App\Events\LiveStream;

use App\Models\LiveStream;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PinChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $livestream;

    public $chatmsg;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(LiveStream $livestream, $chatmsg)
    {
        $this->livestream = $livestream;
        $this->chatmsg = $chatmsg;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('live.chat.'.$this->livestream->profile_id);
    }

    public function broadcastAs()
    {
        return 'chat.pin-message';
    }

    public function broadcastWith()
    {
        return $this->chatmsg;
    }
}
