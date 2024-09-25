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
use Illuminate\Support\Facades\Log;
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
    private int $entityId;

    private array $federationsIDs;

    private string $file;

    public function __construct(int $entityId, array $federationsIDs, string $file)
    {
        $this->entityId = $entityId;
        $this->federationsIDs = $federationsIDs;
        $this->file = $file;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getFederationsIDs(): array
    {
        return $this->federationsIDs;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        foreach ($this->getFederationsIDs() as $federationId) {

            $federation = Federation::withTrashed()->find($federationId);
            if (! $federation) {
                continue;
            }
            try {
                $pathToDirectory = FederationService::getFederationFolder($federation);
            } catch (\Exception $e) {
                $this->fail($e);

                return;
            }
            $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
            $lock = Cache::lock($lockKey, config('constants.lock_constant'));
            try {
                $lock->block(config('constants.lock_constant'));
                EntityFacade::deleteEntityMetadataFromFolder($this->getFile(), $federation->xml_id);

                $entity = Entity::withTrashed()->find($this->getEntityId());
                if ($entity) {
                    NotificationService::sendModelNotification($entity, new EntityStateChanged($entity));
                }

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
}
