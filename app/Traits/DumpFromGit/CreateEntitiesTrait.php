<?php
namespace App\Traits\DumpFromGit;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Federation;
use App\Traits\DumpFromGit\EntitiesHelp\DeleteFromEntity;
use App\Traits\ValidatorTrait;
use DOMNodeList;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait CreateEntitiesTrait{



    use ValidatorTrait;
    use DeleteFromEntity;





    public function createEntities(int $adminId): void
    {
        $xmlfiles = [];
        $tagfiles = [];
        $unknown = [];

        foreach (Storage::files() as $file) {
            if (preg_match('/\.xml$/', $file)) {
                $xmlfiles[] = $file;
            }

            if (preg_match('/\.tag$/', $file)) {
                if (preg_match('/^' . config('git.edugain_tag') . '$/', $file)) {
                    continue;
                }

                $federation = Federation::whereTagfile($file)->first();
                if (!($federation === null && Storage::exists(preg_replace('/\.tag/', '.cfg', $file)))) {
                    $tagfiles[] = $file;
                }
            }
        }
        $tagfiles[] = config('git.edugain_tag');
        $entities = Entity::select('file')->get()->pluck('file')->toArray();

        foreach ($xmlfiles as $xmlfile) {
            if (in_array($xmlfile, $entities)) {
                continue;
            }
            $metadata = Storage::get($xmlfile);

            $xml_file = $this->deleteTags($metadata);

            $metadata = $this->parseMetadata($metadata);

            $entity = json_decode($metadata, true);

            $unknown[$xmlfile]['type'] = $entity['type'];
            $unknown[$xmlfile]['entityid'] = $entity['entityid'];
            $unknown[$xmlfile]['file'] = $xmlfile;
            $unknown[$xmlfile]['xml_file'] = $xml_file;
            $unknown[$xmlfile]['name_en'] = $entity['name_en'];
            $unknown[$xmlfile]['name_cs'] = $entity['name_cs'];
            $unknown[$xmlfile]['description_en'] = $entity['description_en'];
            $unknown[$xmlfile]['description_cs'] = $entity['description_cs'];
            $unknown[$xmlfile]['metadata'] = $entity['metadata'];

            foreach ($tagfiles as $tagfile) {
                $content = Storage::get($tagfile);
                $pattern = preg_quote($entity['entityid'], '/');
                $pattern = "/^$pattern\$/m";

                if (preg_match_all($pattern, $content)) {
                    if (strcmp($tagfile, config('git.edugain_tag')) === 0) {
                        $unknown[$xmlfile]['edugain'] = 1;
                        continue;
                    }

                    $federation = Federation::whereTagfile($tagfile)->first();
                    $unknown[$xmlfile]['federations'][] = $federation ?? null;
                }
            }
        }
        foreach ($unknown as $ent) {
            Db::transaction(function () use ($adminId, $ent) {
                $entity = Entity::create($ent);

                $entity->approved = true;
                $entity->update();
                foreach ($ent['federations'] as $fed) {
                    if (!empty($fed)) {
                        $entity->federations()->attach($fed, [
                            'requested_by' => $adminId,
                            'approved_by' => $adminId,
                            'approved' => true,
                            'explanation' => 'Imported from Git repository.',
                        ]);
                    }
                }

            });
        }
        $hfd = array_filter(preg_split("/\r\n|\r|\n/", Storage::get(config('git.hfd'))));
        foreach ($hfd as $entityid) {
            Entity::whereEntityid($entityid)->update(['hfd' => true]);
        }

        $rs = array_filter(preg_split("/\r\n|\r|\n/", Storage::get(config('git.ec_rs'))));
        foreach ($rs as $entityid) {
            Entity::whereEntityid($entityid)->update(['rs' => true]);
        }

    }
}
