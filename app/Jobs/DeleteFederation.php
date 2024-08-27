<?php

namespace App\Jobs;

use App\Services\FederationService;
use App\Traits\HandlesJobsFailuresTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;

class DeleteFederation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * trait with failure  function
     */
    use HandlesJobsFailuresTrait;

    public string $folderName;

    /**
     * Create a new job instance.
     */
    public function __construct(string $folderName)
    {
        $this->folderName = $folderName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        try {
            $pathToDirectory = FederationService::getFederationFolderByXmlId($this->folderName);
        } catch (\Exception $e) {
            $this->fail($e);

            return;
        }
        dump($pathToDirectory);

        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
        $lock = Cache::lock($lockKey, 61);
        try {
            $lock->block(61);
            FederationService::deleteFederationFolderByXmlId($this->folderName);

        } catch (Exception $e) {
            $this->fail($e);
        } finally {
            if ($lock->isOwnedByCurrentProcess()) {
                $lock->release();
            }
        }

    }
}
