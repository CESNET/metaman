<?php

namespace App\Jobs;

use App\Facades\EntityFacade;
use App\Models\Entity;
use App\Models\Federation;
use App\Notifications\EntityStateChanged;
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

class FolderDeleteEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * trait with failure  function
     */
    use HandlesJobsFailuresTrait;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $entityId,
        public array $federationsIDs,
        public string $file)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        foreach ($this->federationsIDs as $federationId) {

            $federation = Federation::withTrashed()->find($federationId);
            if (! $federation) {
                continue;
            }
            try {
                $pathToDirectory = FederationService::getFederationFolder($federation);
            } catch (\Exception $e) {
                $this->fail($e->getMessage());

                return;
            }
            $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
            $lock = Cache::lock($lockKey, 61);
            try {
                $lock->block(61);
                EntityFacade::deleteEntityMetadataFromFolder($this->file, $federation->xml_id);

                $entity = Entity::withTrashed()->find($this->entityId);
                if ($entity) {
                    NotificationService::sendModelNotification($entity, new EntityStateChanged($entity));
                }

                RunMdaScript::dispatch($federation->id, $lock->owner());
            } catch (Exception $e) {
                $this->fail($e);
            } finally {
                if ($lock->isOwnedByCurrentProcess()) {
                    $lock->release();
                }
            }

        }

    }
}
