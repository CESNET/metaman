<?php

namespace App\Listeners;

use App\Events\FederationApprove;
use App\Services\FederationService;
use Illuminate\Support\Facades\Storage;

class CreateFederationFolder
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
    public function handle(FederationApprove $event): void
    {
        $diskName = config('storageCfg.name');

        $federation = $event->federation;
        if ($federation->approved) {
            if (! Storage::disk($diskName)->exists($federation->name)) {
                FederationService::createFederationFolder($federation->name);
            }
        }

    }
}
