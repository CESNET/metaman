<?php

namespace App\Observers;

use App\Jobs\FolderAddMembership;
use App\Jobs\FolderDeleteMembership;
use App\Models\Membership;

class MembershipObserver
{
    /**
     * Handle the Membership "created" event.
     */
    public function created(Membership $membership): void
    {
        if ($membership->approved == 1) {
            FolderAddMembership::dispatch($membership);
        }
    }

    /**
     * Handle the Membership "updated" event.
     */
    public function updated(Membership $membership): void
    {
        if ($membership->approved == 1) {
            FolderAddMembership::dispatch($membership);
        }
    }

    /**
     * Handle the Membership "deleted" event.
     */
    public function deleted(Membership $membership): void
    {
        if (! $membership->entity->approved) {
            $membership->entity->forceDelete();
        } else {
            $entity = $membership->entity;
            $federation = $membership->federation;
            FolderDeleteMembership::dispatch($entity, $federation);
        }

    }
}
