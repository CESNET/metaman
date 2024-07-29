<?php

namespace App\Observers;

use App\Jobs\DeleteFederation;
use App\Jobs\RestoreFederation;
use App\Jobs\RunMdaScript;
use App\Models\Federation;
use App\Services\FederationService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
        if ($federation->approved && $federation->wasChanged('approved')) {
            FederationService::createFederationFolder($federation);
        }
    }

    /**
     * Handle the Federation "deleted" event.
     */
    public function deleted(Federation $federation): void
    {
        if ($federation->approved) {
            DeleteFederation::dispatch($federation);
        }
    }

    /**
     * Handle the Federation "restored" event.
     */
    public function restored(Federation $federation): void
    {
        $memberships = $federation->memberships;
        if ($memberships->count() == 0) {
            return;
        }

        $jobs = [];
        $diskName = config('storageCfg.name');
        $pathToDirectory = Storage::disk($diskName)->path($federation->name);

        foreach ($memberships as $membership) {
            $jobs[] = new RestoreFederation($membership);
        }
        FederationService::createFederationFolder($federation);
        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
        $lock = Cache::lock($lockKey, 120);

        Log::info('Branch Start');
        try {
            $lock->block(120);
            Bus::batch($jobs)->then(function () use ($federation, $lock) {
                Log::info('Federation restored');
                RunMdaScript::dispatch($federation, $lock->owner());
            })->dispatch();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        } finally {
            if ($lock->isOwnedByCurrentProcess()) {
                $lock->release();
            }
        }

    }

    /**
     * Handle the Federation "force deleted" event.
     */
    public function forceDeleted(Federation $federation): void
    {
        //
    }
}
