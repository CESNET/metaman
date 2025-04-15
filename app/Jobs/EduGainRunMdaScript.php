<?php

namespace App\Jobs;

use App\Traits\HandlesJobsFailuresTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;

class EduGainRunMdaScript implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use HandlesJobsFailuresTrait;

    public string $owner;

    /**
     * Create a new job instance.
     */
    public function __construct(string $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $diskName = config('storageCfg.name');
        $folderName = config('storageCfg.edu2edugain');

        $pathToDirectory = Storage::disk($diskName)->path($folderName);
        $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
        $scriptPath = config('storageCfg.mdaScript');
        $realScriptPath = realpath($scriptPath);

        try {
            $file = config('storageCfg.mdaConfigFolder').'/'.escapeshellarg($folderName).'.xml';
            $pipeline = 'main';
            $command = 'sh '.escapeshellarg($realScriptPath).' '.$file.' '.$pipeline;
            $result = Process::run($command);

            if ($result->failed() || str_contains($result->output(), 'ERROR') || str_contains($result->output(), 'WARN')) {
                Log::error('Script execution error '.$command.' Message: '.$result->output());
            }

        } catch (Exception $e) {
            $this->fail($e);
        } finally {
            Cache::restoreLock($lockKey, $this->owner)->release();

        }

    }
}
