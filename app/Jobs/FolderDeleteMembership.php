<?php

namespace App\Jobs;

use App\Facades\EntityFacade;
use App\Models\Entity;
use App\Models\Federation;
use App\Notifications\MembershipRejected;
use App\Services\FederationService;
use App\Services\NotificationService;
use App\Traits\HandlesJobsFailuresTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;

class FolderDeleteMembership implements ShouldQueue
{
    use Dispatchable, HandlesJobsFailuresTrait, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Entity $entity,
        private Federation $federation
    ) {}

    public function getFederation(): Federation
    {
        return $this->federation;
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $federation = $this->getFederation();
        $entity = $this->getEntity();
        $diskName = config('storageCfg.name');

        try {
            $pathToDirectory = FederationService::getFederationFolder($federation);
        } catch (\Exception $e) {
            $this->fail($e);

            return;
        }

        $pathToFile = $federation->xml_id.'/'.$entity->file;

        if (! Storage::disk($diskName)->exists($pathToFile)) {
            NotificationService::sendModelNotification($entity, new MembershipRejected($entity->entityid, $federation->name));

            return;
        }

        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
        $lock = Cache::lock($lockKey, config('constants.lock_constant'));

        try {
            $lock->block(config('constants.lock_constant'));
            EntityFacade::deleteEntityMetadataFromFolder($entity->file, $federation->xml_id);

            if ($lock->owner() === null) {
                Log::warning("Lock owner is null for key: $lockKey");

                return;
            }

            RunMdaScript::dispatch($federation->id, $lock->owner());
        } catch (Exception $e) {
            $this->fail($e);
        } finally {
            if ($lock->isOwnedByCurrentProcess()) {
                $lock->release();
            } else {
                Log::warning("Lock not owned by current process or lock lost for key: $lockKey");
            }
        }
    }
}
