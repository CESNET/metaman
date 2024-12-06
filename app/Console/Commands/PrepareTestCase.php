<?php

namespace App\Console\Commands;

use App\Traits\DumpFromGit\CreateCategoriesAndGroupsTrait;
use App\Traits\DumpFromGit\CreateFederationTrait;
use App\Traits\EdugainTrait;
use App\Traits\GitTrait;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

class PrepareTestCase extends Command implements Isolatable
{
    use CreateCategoriesAndGroupsTrait,CreateFederationTrait,
        EdugainTrait,
        GitTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prefed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare test case for federation groups and categories';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('migrate:refresh');
        $this->call('db:seed');

        $this->initializeGit();
        $this->createFederations();
        $this->createCategoriesAndGroups();
        $this->makeEdu2Edugain();

    }
}
