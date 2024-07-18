<?php

namespace App\Events;

use App\Models\Membership;
use Illuminate\Broadcasting\InteractsWithSockets;
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
