<?php

namespace App\Jobs;

use App\Facades\EntityFacade;
use App\Models\Entity;
use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

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

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO add aproveChecker to this query
        $federationMembershipId = Membership::select('federation_id')
            ->where('entity_id', $this->entity->id)
            ->get();

        foreach ($federationMembershipId as $fedId) {
            EntityFacade::saveMetadataToFederationFolder($this->entity->id, $fedId->federation_id);
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
