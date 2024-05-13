<?php

namespace App\Jobs;

use App\Facades\EntityFacade;
use App\Models\Entity;
use App\Models\Federation;
use App\Traits\FederationTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SaveMetadataToFolders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    protected  int $entity_id;
    protected  int $federation_id;

    public function __construct($entity_id, $federation_id)
    {
        $this->entity_id = $entity_id;
        $this->federation_id = $federation_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        EntityFacade::SaveEntityMetadataToFile($this->entity_id,$this->federation_id);

    }
}
