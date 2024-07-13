<?php

namespace App\Jobs;

use App\Models\Federation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;

class RunMdaScript implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Federation $federation;

    public string $owner;

    /**
     * Create a new job instance.
     */
    public function __construct(Federation $federation, string $owner)
    {
        $this->federation = $federation;
        $this->owner = $owner;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $diskName = config('storageCfg.name');
        $pathToDirectory = Storage::disk($diskName)->path($this->federation->name);
        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';

        $filterArray = explode(', ', $this->federation->filters);
        $scriptPath = config('storageCfg.mdaScript');
        $command = 'sh '.config('storageCfg.mdaScript');

        $realScriptPath = realpath($scriptPath);

        try {

            foreach ($filterArray as $filter) {
                $file = escapeshellarg($filter).'.xml';
                $pipeline = 'main';
                $command = 'sh '.escapeshellarg($realScriptPath).' '.$file.' '.$pipeline;

                $res = shell_exec($command);
                dump($res);
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
     */
    public function middleware(): array
    {
        $diskName = config('storageCfg.name');
        $pathToDirectory = Storage::disk($diskName)->path($this->federation->name);
        $lockKey = 'directory-' . md5($pathToDirectory) . '-lock';

        return [
            new RateLimited('mda-run-limit'),
            (new WithoutOverlapping($lockKey))->dontRelease()
        ];

    }
}
