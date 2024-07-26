<?php

namespace App\Jobs;

use App\Facades\EntityFacade;
use App\Models\Membership;
use App\Traits\HandlesJobsFailuresTrait;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mockery\Exception;

class RestoreFederation implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use HandlesJobsFailuresTrait;

    public Membership $membership;

    /**
     * Create a new job instance.
     */
    public function __construct(Membership $membership)
    {
        $this->membership = $membership;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            $this->fail(new Exception('batch was cancelled'));
        }
        try {
            $federation = $this->membership->federation;
            $entity = $this->membership->entity;
            EntityFacade::saveMetadataToFederationFolder($entity->id, $federation->id);

        } catch (Exception $e) {
            $this->fail($e);
        }

    }
}
