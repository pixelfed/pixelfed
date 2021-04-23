<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\User;

class NewMention implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $user;
    protected $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, $data)
    {
        $this->user = $user;
        $this->data = $data;
    }

    public function broadcastAs()
    {
        return 'notification.new.mention';
    }

    public function broadcastOn()
    {
        return new PrivateChannel('App.User.' . $this->user->id);
    }

    public function broadcastWith()
    {
        return ['id' => $this->user->id];
    }

    public function via()
    {
        return 'broadcast';
    }
}
