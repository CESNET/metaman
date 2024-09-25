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
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class DeleteFederation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * trait with failure  function
     */
    use HandlesJobsFailuresTrait;

    private string $folderName;

    /**
     * Create a new job instance.
     */
    public function __construct(string $folderName)
    {
        $this->folderName = $folderName;
    }

    public function getFolderName(): string
    {
        return $this->folderName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        try {
            $pathToDirectory = FederationService::getFederationFolderByXmlId($this->getFolderName());
        } catch (\Exception $e) {
            $this->fail($e);

            return;
        }

        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
        $lock = Cache::lock($lockKey, config('constants.lock_constant'));
        try {
            $lock->block(config('constants.lock_constant'));
            FederationService::deleteFederationFolderByXmlId($this->getFolderName());

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
