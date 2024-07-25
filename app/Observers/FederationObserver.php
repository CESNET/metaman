<?php

namespace App\Observers;

use App\Models\Federation;
use App\Services\FederationService;
use Illuminate\Support\Facades\Storage;

class FederationObserver
{
    /**
     * Handle the Federation "created" event.
     */
    public function created(Federation $federation): void
    {
        //
    }

    /**
     * Handle the Federation "updated" event.
     */
    public function updated(Federation $federation): void
    {
        $diskName = config('storageCfg.name');
        if ($federation->approved && $federation->wasChanged('approved')) {
            if (! Storage::disk($diskName)->exists($federation->name)) {
                FederationService::createFederationFolder($federation->name);
            }
        }
    }

    /**
     * Handle the Federation "deleted" event.
     */
    public function deleted(Federation $federation): void
    {
        //
    }

    /**
     * Handle the Federation "restored" event.
     */
    public function restored(Federation $federation): void
    {
        //
    }

    /**
     * Handle the Federation "force deleted" event.
     */
    public function forceDeleted(Federation $federation): void
    {
        //
    }
}
