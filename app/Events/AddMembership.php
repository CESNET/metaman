<?php

namespace App\Events;

use App\Models\Membership;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AddMembership
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Membership $membership;


    /**
     * Create a new event instance.
     */
    public function __construct(Membership $membership)
    {
        $this->membership = $membership;
    }

}
