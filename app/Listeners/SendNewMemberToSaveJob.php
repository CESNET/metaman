<?php

namespace App\Listeners;

use App\Events\AddMembership;
use App\Jobs\EduGainAddEntity;
use App\Jobs\FolderAddEntity;
use App\Models\Entity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendNewMemberToSaveJob
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AddMembership $event): void
    {
        $entityId = $event->membership->entity_id;
        $entity =  Entity::find($entityId);


        if ($entity->approved == 1 && $event->membership->approved == 1 ) {
            Log::info(" dispatch FolderAddEntity from Listener SendNewMemberToSaveJob");
        }

    }
}
