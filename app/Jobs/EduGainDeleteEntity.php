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

class EduGainDeleteEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * trait with failure  function
     */
    use EdugainTrait,HandlesJobsFailuresTrait;

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
        $diskName = config('storageCfg.name');
        $folderName = config('storageCfg.edu2edugain');

        if (! Storage::disk($diskName)->exists($folderName)) {
            $this->makeEdu2Edugain();
        }

        try {
            if (! Storage::disk($diskName)->exists($folderName)) {
                throw new Exception("No $folderName in $diskName");
            }
        } catch (Exception $e) {
            $this->fail($e);
        }
        $pathToDirectory = Storage::disk($diskName)->path($folderName);
        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
        $lock = Cache::lock($lockKey, 61);
        try {
            $lock->block(61);
            EntityFacade::deleteEntityMetadataFromFolder($this->entity->file, $folderName);
            NotificationService::sendEntityNotification($this->entity, new EntityEdugainStatusChanged($this->entity));
            EduGainRunMdaScript::dispatch($lock->owner());
        } catch (Exception $e) {
            Log::error($e->getMessage());
        } finally {
            if ($lock->isOwnedByCurrentProcess()) {
                $lock->release();
            }
        }
    }
}
