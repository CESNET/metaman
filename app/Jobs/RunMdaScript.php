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
use Illuminate\Support\Facades\Process;
use Mockery\Exception;

class RunMdaScript implements ShouldQueue
{
    use Dispatchable, HandlesJobsFailuresTrait, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $federationId,
        public string $owner
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $federation = Federation::withTrashed()->find($this->federationId);
        if (! $federation) {
            $this->fail(new \Exception("Federation with id {$this->federationId} not found"));

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
        $scriptPath = config('metaman.mdaScript');

        $realScriptPath = realpath($scriptPath);

        try {

            foreach ($filterArray as $filter) {
                $file = config('metaman.mdaConfigFolder').'/'.escapeshellarg($filter).'.xml';
                $pipeline = 'main';
                $command = 'bash '.escapeshellarg($realScriptPath).' '.$file.' '.$pipeline;
                $result = Process::run($command);

                if ($result->failed() || str_contains($result->output(), 'ERROR') || str_contains($result->output(), 'WARN')) {
                    Log::error('Script execution error '.$command.' Message: '.$result->output());
                }
            }
        } catch (Exception $e) {
            Log::error($e);
        } finally {
            Cache::restoreLock($lockKey, $this->owner)->release();
        }
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @codeCoverageIgnore
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
