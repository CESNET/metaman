<?php

namespace App\Listeners;

use App\Events\AddMembership;
use App\Jobs\FolderAddMembership;

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

        if ($event->membership->approved == 1) {
            FolderAddMembership::dispatch($event->membership);
        }

    }
}
