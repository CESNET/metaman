<?php

namespace App\Console\Commands;

use App\Models\Entity;
use App\Traits\DumpFromGit\EntitiesHelp\FixEntityTrait;
use App\Traits\ValidatorTrait;
use Illuminate\Console\Command;

class ValidateMetaConsole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:val';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    use ValidatorTrait,FixEntityTrait;

    /**
     * Execute the console command.
     */

    private function doc()
    {
        foreach (Entity::select()->get() as $entity)
        {
            $ent = Entity::where('id', $entity->id)->select()->first();


            // $res = json_decode($this->validateMetadata($ent->metadata),true);
            $res = json_decode($this->validateMetadata($ent->xml_file,true),true);
            $res['ent_id'] = $ent->id;
            $errorArray = $res['errorArray'];


            if($res['code']==1)
            {
                dump($res);
            }
            else
            {
                dump($res['ent_id']);
            }
        }
    }

    private function meta()
    {
        foreach (Entity::select()->get() as $entity)
        {

            $ent = Entity::where('id', $entity->id)->select()->first();

            $curr = 345;

            if($ent->id < $curr)
                continue;
            if($ent->id > $curr)
                break;


            $res = json_decode($this->validateMetadata($ent->metadata),true);
            $res['ent_id'] = $ent->id;


            dump($res);
            if( $res['code']==1)
            {

            }
        }
    }


    public function handle()
    {

       // $this->fixEntities();
        $this->doc();


    }
}
