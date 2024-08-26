<?php

namespace App\Jobs;

use App\Facades\EntityFacade;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Membership;
use App\Notifications\MembershipAccepted;
use App\Services\FederationService;
use App\Services\NotificationService;
use App\Traits\HandlesJobsFailuresTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;

class FolderAddMembership implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use HandlesJobsFailuresTrait;

    public Membership $membership;

    /**
     * Create a new job instance.
     */
    public function __construct(Membership $membership)
    {
        $this->membership = $membership;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $federation = Federation::find($this->membership->federation_id);
        $entity = Entity::find($this->membership->entity_id);

        try {
            $pathToDirectory = FederationService::getFederationFolder($federation);
        } catch (\Exception $e) {
            $this->fail($e);

            return;
        }

        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
        $lock = Cache::lock($lockKey, 61);

        try {
            $lock->block(61);
            EntityFacade::saveMetadataToFederationFolder($entity->id, $federation->id);

            NotificationService::sendModelNotification($entity, new MembershipAccepted($this->membership));

            RunMdaScript::dispatch($federation, $lock->owner());
        } catch (Exception $e) {
            $this->fail($e);
        } finally {
            if ($lock->isOwnedByCurrentProcess()) {
                $lock->release();
            }
        }

    }
}
