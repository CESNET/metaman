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
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;

class FolderDeleteMembership implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use HandlesJobsFailuresTrait;

    public Federation $federation;

    public Entity $entity;

    /**
     * Create a new job instance.
     */
    public function __construct(Entity $entity, Federation $federation)
    {
        $this->federation = $federation;
        $this->entity = $entity;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $federation = $this->federation;
        $entity = $this->entity;
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
        $lock = Cache::lock($lockKey, 61);

        try {
            $lock->block(61);
            EntityFacade::deleteEntityMetadataFromFolder($entity->file, $federation->xml_id);
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
