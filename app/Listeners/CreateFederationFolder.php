<?php

namespace App\Listeners;

use App\Events\FederationApprove;
use App\Traits\FederationTrait;
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
        $diskName = config('storageCfg.name');

        $federation = $event->federation;
        if ($federation->approved) {
            if (! Storage::disk($diskName)->exists($federation->name)) {
                $this->createFederationFolder($federation->name);
            }
        }

    }
}
