<?php

namespace App\Events;

use App\Models\Entity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeleteEntity
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
