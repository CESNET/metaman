<?php

namespace App\Listeners;

use App\Events\DeleteEntity;
use App\Facades\EntityFacade;
use App\Jobs\FolderDeleteEntity;
use Illuminate\Support\Facades\Log;

class SendDeletedEntityToDeleteJob
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
    public function handle(DeleteEntity $event): void
    {
          FolderDeleteEntity::dispatch($event->entity);
    }
}
