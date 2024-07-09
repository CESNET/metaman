<?php

namespace App\Console\Commands;

use App\Models\Entity;
use App\Models\Federation;
use App\Traits\DumpFromGit\EntitiesHelp\FixEntityTrait;
use App\Traits\ValidatorTrait;
use Exception;
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

    use FixEntityTrait,ValidatorTrait;

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

    public function handle()
    {
        $federation = Federation::where('id', 1)->first();
        $this->runMDA($federation);

        // $this->fixEntities();
        //  $this->doc();

    }
}
