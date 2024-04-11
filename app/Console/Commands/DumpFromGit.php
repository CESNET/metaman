<?php

namespace App\Console\Commands;


use App\Models\User;
use App\Traits\DumpFromGit\CreateEntitiesTrait;
use App\Traits\DumpFromGit\CreateFederationTrait;
use App\Traits\GitTrait;
use Illuminate\Console\Command;
use App\Traits\ValidatorTrait;

class DumpFromGit extends Command
{
    use GitTrait, ValidatorTrait;
    use CreateFederationTrait,CreateEntitiesTrait;

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
    protected $description = 'Command description';




    /**
     * Execute the console command.
     */
    public function handle()
    {

        $firstAdminId = User::where('admin', 1)->first()->id;
        $this->initializeGit();
        $this->createFederations();
        $this->createEntities($firstAdminId);

    }
}
