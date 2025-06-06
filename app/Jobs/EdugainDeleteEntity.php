<?php

namespace App\Jobs;

use App\Facades\EntityFacade;
use App\Models\Entity;
use App\Notifications\EntityEdugainStatusChanged;
use App\Services\NotificationService;
use App\Traits\EdugainTrait;
use App\Traits\HandlesJobsFailuresTrait;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EdugainDeleteEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * trait with failure  function
     */
    use EdugainTrait, HandlesJobsFailuresTrait;

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
        $diskName = config('metaman.metadata');
        $folderName = config('metaman.eduid2edugain');

        if (! Storage::disk($diskName)->exists($folderName)) {
            $this->fail(new Exception("No $folderName in Disk"));

            return;
        }

        $pathToDirectory = Storage::disk($diskName)->path($folderName);
        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
        $lock = Cache::lock($lockKey, config('constants.lock_constant'));
        try {
            $lock->block(config('constants.lock_constant'));
            EntityFacade::deleteEntityMetadataFromFolder($this->getEntity()->file, $folderName);
            NotificationService::sendModelNotification($this->getEntity(), new EntityEdugainStatusChanged($this->getEntity()));

            if ($lock->owner() === null) {
                Log::warning("Lock owner is null for key: $lockKey");

                return;
            }

            EdugainRunMdaScript::dispatch($lock->owner());
        } catch (Exception $e) {
            Log::error($e->getMessage());
        } finally {
            if ($lock->isOwnedByCurrentProcess()) {
                $lock->release();
            } else {
                Log::warning("Lock not owned by current process or lock lost for key: $lockKey");
            }
        }
    }
}
