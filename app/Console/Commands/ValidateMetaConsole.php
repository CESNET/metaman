<?php

namespace App\Console\Commands;

use App\Jobs\RunMdaScript;
use App\Models\Entity;
use App\Models\Federation;
use App\Traits\DumpFromGit\EntitiesHelp\FixEntityTrait;
use App\Traits\DumpFromGit\EntitiesHelp\UpdateEntity;
use App\Traits\ValidatorTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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

    use FixEntityTrait,UpdateEntity,ValidatorTrait;

    /**
     * Execute the console command.
     */
    private function doc()
    {
        foreach (Entity::select()->get() as $entity) {
            $ent = Entity::where('id', $entity->id)->select()->first();

            // $res = json_decode($this->validateMetadata($ent->metadata),true);
            $res = json_decode($this->validateMetadata($ent->xml_file, true), true);
            $res['ent_id'] = $ent->id;
            $errorArray = $res['errorArray'];

            if ($res['code'] == 1) {
                dump($res);
            } else {
                dump($res['ent_id']);
            }
        }
    }

    private function meta()
    {
        foreach (Entity::select()->get() as $entity) {

            $ent = Entity::where('id', $entity->id)->select()->first();

            $curr = 345;

            if ($ent->id < $curr) {
                continue;
            }
            if ($ent->id > $curr) {
                break;
            }

            $res = json_decode($this->validateMetadata($ent->metadata), true);
            $res['ent_id'] = $ent->id;

            dump($res);
            if ($res['code'] == 1) {

            }
        }
    }

    private function runMDA(Federation $federation)
    {
        $filterArray = explode(', ', $federation->filters);

        $scriptPath = config('storageCfg.mdaScript');
        $command = 'sh '.config('storageCfg.mdaScript');

        $realScriptPath = realpath($scriptPath);

        if ($realScriptPath === false) {
            throw new Exception('file not exist'.$scriptPath);
        }

        foreach ($filterArray as $filter) {
            $file = escapeshellarg($filter).'.xml';
            $pipeline = 'main';
            $command = 'sh '.escapeshellarg($realScriptPath).' '.$file.' '.$pipeline;

            $res = shell_exec($command);
            dump($res);
        }
    }

    public function RunValidate($metadata)
    {
        $res = json_decode($this->validateMetadata($metadata, true), true);
        $errorArray = $res['errorArray'];
        dump($errorArray);
        if (empty($errorArray)) {
            dump('no error');
        }
    }

    public function handle()
    {
        /*        $xml1 = '';
                $this->RunValidate($xml1);*/

        /*                $entity = Entity::find(1);
                        $xml_document = $entity->xml_file;
                        $groupLink = $entity->groups()->pluck('xml_value')->toArray();

                        dump($groupLink);

                        $xml_document = $this->updateXmlGroups($xml_document, $groupLink);
                        dump($xml_document);*/

        /*        if(!empty($entity->groups)) {
                    $groups = $entity->groups()->pluck('name')->toArray();
                    foreach ($groups as $name) {
                        $configValue = config("groups.$name");
                        dump($configValue);
                    }*/
    }

    /*        $lockKey = 'directory-'.md5('aboba').'-lock';
            $lock = Cache::lock($lockKey, 61);
            RunMdaScript::dispatch(2, $lock->owner());
            $lock->release();*/

    /*        $federation = Federation::where('id', 1)->first();
            $this->runMDA($federation);*/

    // $this->fixEntities();
    //  $this->doc();

}
