<?php

namespace App\Jobs;

use App\Facades\EntityFacade;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Membership;
use App\Notifications\EntityStateChanged;
use App\Notifications\EntityUpdated;
use App\Services\NotificationService;
use App\Traits\HandlesJobsFailuresTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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

        $diskName = config('storageCfg.name');

        foreach ($federationMembershipId as $fedId) {

            $federation = Federation::where('id', $fedId->federation_id)->first();

            if (! Storage::disk($diskName)->exists($federation->name)) {
                continue;
            }
            $pathToDirectory = Storage::disk($diskName)->path($federation->name);
            $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
            $lock = Cache::lock($lockKey, 61);

            try {
                $lock->block(61);
                EntityFacade::saveMetadataToFederationFolder($this->entity->id, $fedId->federation_id);

                if ($this->entity->wasChanged('deleted_at') && is_null($this->entity->deleted_at)) {
                    NotificationService::sendEntityNotification($this->entity, new EntityStateChanged($this->entity));
                } else {
                    if ($this->entity->wasChanged('approved') && $this->entity->approved == 1) {
                        NotificationService::sendEntityNotification($this->entity, new EntityStateChanged($this->entity));
                    } else {
                        NotificationService::sendEntityNotification($this->entity, new EntityUpdated($this->entity));
                    }
                }

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

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->entity->id))->dontRelease()];
    }
}
