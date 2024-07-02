<?php

namespace App\Listeners;

use App\Events\FederationApprove;
use App\Traits\FederationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

class CreateFederationFolder
{
    use FederationTrait;

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
    public function handle(FederationApprove $event): void
    {

        $federation = $event->federation;
        if($federation->approved)
        {
            if(!Storage::disk('metadata')->exists($federation->name)){
                $this->createFederationFolder($federation->name);
            }
        }

    }
}
