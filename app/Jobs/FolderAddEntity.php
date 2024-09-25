<?php

namespace App\Jobs;

use App\Facades\EntityFacade;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Membership;
use App\Notifications\EntityStateChanged;
use App\Notifications\EntityUpdated;
use App\Services\FederationService;
use App\Services\NotificationService;
use App\Traits\HandlesJobsFailuresTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class FolderAddEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * trait with failure  function
     */
    use HandlesJobsFailuresTrait;

    private Entity $entity;

    /**
     * Create a new job instance.
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
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
        $federationMembershipId = Membership::select('federation_id')
            ->where('entity_id', $this->getEntity()->id)
            ->where('approved', 1)
            ->get();

        foreach ($federationMembershipId as $fedId) {

            $federation = Federation::where('id', $fedId->federation_id)->first();

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
                EntityFacade::saveMetadataToFederationFolder($this->getEntity()->id, $fedId->federation_id);

                if (
                    ($this->getEntity()->wasChanged('deleted_at') && is_null($this->getEntity()->deleted_at)) ||
                    ($this->getEntity()->wasChanged('approved') && $this->getEntity()->approved == 1)
                ) {
                    NotificationService::sendModelNotification($this->getEntity(), new EntityStateChanged($this->getEntity()));
                } else {
                    NotificationService::sendModelNotification($this->getEntity(), new EntityUpdated($this->getEntity()));
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

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->getEntity()->id)
        )];
    }
}
