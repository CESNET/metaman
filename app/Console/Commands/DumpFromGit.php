<?php

namespace App\Console\Commands;

use App\Facades\EntityFacade;
use App\Models\Membership;
use App\Models\User;
use App\Traits\DumpFromGit\CreateCategoriesAndGroupsTrait;
use App\Traits\DumpFromGit\CreateEntitiesTrait;
use App\Traits\DumpFromGit\CreateFederationTrait;
use App\Traits\DumpFromGit\EntitiesHelp\FixEntityTrait;
use App\Traits\DumpFromGit\EntitiesHelp\UpdateEntity;
use App\Traits\EdugainTrait;
use App\Traits\FederationTrait;
use App\Traits\GitTrait;
use App\Traits\ValidatorTrait;
use Exception;
use Illuminate\Console\Command;

class DumpFromGit extends Command
{
    use CreateCategoriesAndGroupsTrait,CreateEntitiesTrait,CreateFederationTrait;
    use EdugainTrait;
    use FederationTrait,FixEntityTrait,UpdateEntity;
    use GitTrait, ValidatorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dump-from-git';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump all old information from git';

    private function createMetadataFiles(): void
    {
        $this->updateFederationFolders();
        $membership = Membership::select('entity_id', 'federation_id')->whereApproved(1)->get();
        foreach ($membership as $member) {
            EntityFacade::saveMetadataToFederationFolder($member->entity_id, $member->federation_id);
        }
    }

    /**
     * Execute the console command.
     *
     * @throws Exception no amin
     */
    public function handle()
    {
        $firstAdminId = User::where('admin', 1)->first()->id;
        if (empty($firstAdminId)) {
            throw new Exception('firstAdminId is null');
        }

        $this->initializeGit();
        $this->createFederations();
        $this->createEntities($firstAdminId);
        $this->createCategoriesAndGroups();
        $this->updateGroupsAndCategories();
        $this->updateEntitiesXml();
        $this->updateFederationFolders();
        $this->fixEntities();
        $this->createMetadataFiles();
        $this->makeEdu2Edugain();

    }
}
