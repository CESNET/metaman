<?php

namespace App\Jobs;

use App\Models\Federation;
use App\Services\FederationService;
use App\Traits\HandlesJobsFailuresTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class RunMdaScript implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use HandlesJobsFailuresTrait;

    public int $federationId;

    public string $owner;

    /**
     * Create a new job instance.
     */
    public function __construct(int $federationId, string $owner)
    {
        $this->federationId = $federationId;
        $this->owner = $owner;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $federation = Federation::withTrashed()->find($this->federationId);
        if (! $federation) {
            $this->fail("Federation with id {$this->federationId} not found");

            return;
        }

        try {
            $pathToDirectory = FederationService::getFederationFolder($federation);
        } catch (\Exception $e) {
            $this->fail($e);

            return;
        }

        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';

        $filterArray = explode(', ', $federation->filters);
        $scriptPath = config('storageCfg.mdaScript');

        $realScriptPath = realpath($scriptPath);

        try {

            foreach ($filterArray as $filter) {
                $file = config('storageCfg.mdaConfigFolder').'/'.escapeshellarg($filter).'.xml';
                $pipeline = 'main';
                $command = 'sh '.escapeshellarg($realScriptPath).' '.$file.' '.$pipeline;
                shell_exec($command);
            }

        } catch (Exception $e) {
            Log::error($e->getMessage());
        } finally {
            Cache::restoreLock($lockKey, $this->owner)->release();

        }

    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     *
     * @throws \Exception
     */
    public function middleware(): array
    {
        $pathToDirectory = FederationService::getFederationFolderById($this->federationId);
        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';

        return [
            new RateLimited('mda-run-limit'),
            (new WithoutOverlapping($lockKey))->dontRelease(),
        ];

    }
}
