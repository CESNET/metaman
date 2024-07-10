<?php

namespace App\Listeners;

use App\Events\UpdateEntity;
use App\Jobs\FolderAddEntity;
use Illuminate\Support\Facades\Log;

class SendUpdatedEntityToSaveJob
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
    public function handle(UpdateEntity $event): void
    {

        $ent = $event->entity;

        if ($ent->wasChanged('xml_file') ||
            ($ent->wasChanged('approved') && $ent->approved == 1)
        ) {
            FolderAddEntity::dispatch($event->entity);
        }
    }
}
