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
use Mockery\Exception;

class FolderAddEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * trait with failure  function
     */
    use HandlesJobsFailuresTrait;

    public Entity $entity;

    /**
     * Create a new job instance.
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $federationMembershipId = Membership::select('federation_id')
            ->where('entity_id', $this->entity->id)
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
            $lock = Cache::lock($lockKey, 61);

            try {
                $lock->block(61);
                EntityFacade::saveMetadataToFederationFolder($this->entity->id, $fedId->federation_id);

                if (
                    ($this->entity->wasChanged('deleted_at') && is_null($this->entity->deleted_at)) ||
                    ($this->entity->wasChanged('approved') && $this->entity->approved == 1)
                ) {
                    NotificationService::sendModelNotification($this->entity, new EntityStateChanged($this->entity));
                } else {
                    NotificationService::sendModelNotification($this->entity, new EntityUpdated($this->entity));
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

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->entity->id)
        )];
    }
}
