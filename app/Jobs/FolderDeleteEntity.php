<?php

namespace App\Jobs;

use App\Facades\EntityFacade;
use App\Models\Entity;
use App\Notifications\EntityDeletedFromHfd;
use App\Notifications\EntityStateChanged;
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

class FolderDeleteEntity implements ShouldQueue
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

        $entity = $this->entity;
        $federations = $entity->federations;
        $diskName = config('storageCfg.name');
        foreach ($federations as $federation) {
            if (! Storage::disk($diskName)->exists($federation->name)) {
                continue;
            }
            $pathToDirectory = Storage::disk($diskName)->path($federation->name);
            $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
            $lock = Cache::lock($lockKey, 61);
            try {
                $lock->block(61);
                EntityFacade::deleteEntityMetadataFromFolder($entity->file, $federation->xml_id);

                NotificationService::sendEntityNotification($entity, EntityStateChanged::class);
                if ($entity->hfd) {
                    NotificationService::sendEntityNotification($entity, EntityDeletedFromHfd::class);
                }

                RunMdaScript::dispatch($federation, $lock->owner());
            } catch (Exception $e) {
                Log::error($e->getMessage());
            } finally {
                if ($lock->isOwnedByCurrentProcess()) {
                    $lock->release();
                }
            }

        }

    }
}
