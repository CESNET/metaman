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

class FederationObserver
{
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
            DeleteFederation::dispatch($federation->xml_id);
        }
    }

    /**
     * Handle the Federation "restored" event.
     *
     * @throws \Exception|\Throwable no folder
     */
    public function restored(Federation $federation): void
    {
        FederationService::createFederationFolder($federation);
        $memberships = $federation->memberships;
        if ($memberships->count() == 0) {
            return;
        }
        $jobs = [];
        $pathToDirectory = FederationService::getFederationFolder($federation);

        foreach ($memberships as $membership) {
            $jobs[] = new RestoreFederation($membership);
        }
        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
        $lock = Cache::lock($lockKey, 120);

        Log::info('Branch Start');
        try {
            $lock->block(120);
            Bus::batch($jobs)->then(function () use ($federation, $lock) {
                Log::info('Federation restored');
                RunMdaScript::dispatch($federation->id, $lock->owner());
            })->dispatch();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        } finally {
            if ($lock->isOwnedByCurrentProcess()) {
                $lock->release();
            }
        }
    }
}
