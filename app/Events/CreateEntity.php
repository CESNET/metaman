<?php

namespace App\Events;

use App\Models\Entity;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreateEntity
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Entity $entity;

    /**
     * Create a new event instance.
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }
}
