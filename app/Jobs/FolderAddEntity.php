<?php

namespace App\Jobs;

use App\Facades\EntityFacade;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;

class FolderAddEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Entity $entity;

    /**
     * Create a new job instance.
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    private function runMDA(Federation $federation)
    {
        $filterArray = explode(", ", $federation->filters);

        $scriptPath = config('storageCfg.mdaScript');
        $command = "sh " . config('storageCfg.mdaScript');

        $realScriptPath = realpath($scriptPath);

        if ($realScriptPath === false) {
            throw new Exception("file not exist" . $scriptPath);
        }

        foreach ($filterArray as $filter) {
            $file = escapeshellarg($filter) . '.xml';
            $pipeline = 'main';
            $command = 'sh ' . escapeshellarg($realScriptPath) . ' ' . $file . ' ' . $pipeline ;

            $res =  shell_exec($command);
            dump($res);
        }
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO add aproveChecker to this query
        $federationMembershipId = Membership::select('federation_id')
            ->where('entity_id', $this->entity->id)
            ->get();

        $diskName = config('storageCfg.name');

        foreach ($federationMembershipId as $fedId) {


            $federation = Federation::where('id', $fedId->federation_id)->first();


            if (!Storage::disk($diskName)->exists($federation->name)) {
                continue;
            }
            $pathToDirectory = Storage::disk($diskName)->path($federation->name);
            $lockKey = 'directory-' . md5($pathToDirectory) . '-lock';
            $lock = Cache::lock($lockKey,120);

            try {
                EntityFacade::saveMetadataToFederationFolder($this->entity->id, $fedId->federation_id);
                $this->runMDA($federation);
            } catch (Exception $e)
            {
                Log::error($e->getMessage());
            } finally {
                $lock->release();
            }

        }
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->entity->id))->dontRelease()];
    }
}
