<?php

namespace App\Events;

use App\Models\Federation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FederationApprove
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Federation $federation;

    /**
     * Create a new event instance.
     */
    public function __construct(Federation  $federation)
    {
        $this->federation = $federation;
    }

}
