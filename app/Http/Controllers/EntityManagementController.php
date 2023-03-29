<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Federation;
use App\Traits\GitTrait;
use App\Traits\ValidatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EntityManagementController extends Controller
{
    use ValidatorTrait, GitTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('do-everything');

        $this->initializeGit();

        $xmlfiles = [];
        $tagfiles = [];
        foreach (Storage::files() as $file) {
            if (preg_match('/\.xml$/', $file)) {
                $xmlfiles[] = $file;
            }

            if (preg_match('/\.tag$/', $file)) {
                if (preg_match('/^'.config('git.edugain_tag').'$/', $file)) {
                    continue;
                }

                $federation = Federation::whereTagfile($file)->first();
                if ($federation === null && Storage::exists(preg_replace('/\.tag/', '.cfg', $file))) {
                    return redirect()
                        ->route('entities.index')
                        ->with('status', __('entities.missing_federations'))
                        ->with('color', 'red');
                } else {
                    $tagfiles[] = $file;
                }
            }
        }

        $tagfiles[] = config('git.edugain_tag');

        $entities = Entity::select('file')->get()->pluck('file')->toArray();

        $unknown = [];
        foreach ($xmlfiles as $xmlfile) {
            if (in_array($xmlfile, $entities)) {
                continue;
            }

            $metadata = Storage::get($xmlfile);
            $metadata = $this->parseMetadata($metadata);
            $entity = json_decode($metadata, true);

            $unknown[$xmlfile]['type'] = $entity['type'];
            $unknown[$xmlfile]['entityid'] = $entity['entityid'];
            $unknown[$xmlfile]['file'] = $xmlfile;
            $unknown[$xmlfile]['name_en'] = $entity['name_en'];
            $unknown[$xmlfile]['name_cs'] = $entity['name_cs'];
            $unknown[$xmlfile]['description_en'] = $entity['description_en'];
            $unknown[$xmlfile]['description_cs'] = $entity['description_cs'];

            foreach ($tagfiles as $tagfile) {
                $content = Storage::get($tagfile);
                $pattern = preg_quote($entity['entityid'], '/');
                $pattern = "/^$pattern\$/m";

                if (preg_match_all($pattern, $content)) {
                    if (strcmp($tagfile, config('git.edugain_tag')) === 0) {
                        $unknown[$xmlfile]['federations'][] = 'eduGAIN';

                        continue;
                    }

                    $federation = Federation::whereTagfile($tagfile)->first();
                    $unknown[$xmlfile]['federations'][] = $federation->name ?? null;
                }
            }
        }

        if (empty($unknown)) {
            return redirect()
                ->route('entities.index')
                ->with('status', __('entities.nothing_to_import'));
        }

        return view('entities.import', [
            'entities' => $unknown,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('do-everything');

        if (empty(request('entities'))) {
            return back()
                ->with('status', __('entities.empty_import'))
                ->with('color', 'red');
        }

        // ADD SELECTED ENTITIES FROM XML FILES TO DATABASE
        $imported = 0;
        foreach (request('entities') as $xmlfile) {
            $xml_entity = $this->parseMetadata(Storage::get($xmlfile));
            $new_entity = json_decode($xml_entity, true);

            DB::transaction(function () use ($new_entity) {
                $entity = Entity::create($new_entity);

                $entity->approved = true;
                $entity->update();
            });

            $imported++;
        }

        // FIX FEDERATIONS MEMBERSHIP
        foreach (request('entities') as $xmlfile) {
            $entity = Entity::whereFile($xmlfile)->first();

            foreach (Federation::select('id', 'tagfile')->get() as $federation) {
                $members = Storage::get($federation->tagfile);
                $pattern = preg_quote($entity->entityid, '/');
                $pattern = "/^$pattern\$/m";

                if (! preg_match_all($pattern, $members)) {
                    continue;
                }

                $entity->federations()->attach($federation, [
                    'requested_by' => Auth::id(),
                    'approved_by' => Auth::id(),
                    'approved' => true,
                    'explanation' => 'Imported from Git repository.',
                ]);
            }
        }

        // FIX HIDE FROM DISCOVERY MEMBERSHIP
        $hfd = array_filter(preg_split("/\r\n|\r|\n/", Storage::get(config('git.hfd'))));
        foreach ($hfd as $entityid) {
            Entity::whereEntityid($entityid)->update(['hfd' => true]);
        }

        // FIX EDUGAIN MEMBERSHIP
        $edugain = array_filter(preg_split("/\r\n|\r|\n/", Storage::get(config('git.edugain_tag'))));
        foreach ($edugain as $entityid) {
            Entity::whereEntityid($entityid)->update(['edugain' => true]);
        }

        // FIX RESEARCH AND EDUCATION MEMBERSHIP
        $rs = array_filter(preg_split("/\r\n|\r|\n/", Storage::get(config('git.ec_rs'))));
        foreach ($rs as $entityid) {
            Entity::whereEntityid($entityid)->update(['rs' => true]);
        }

        return redirect('entities')
            ->with('status', trans_choice('entities.imported', $imported));
    }

    public function update()
    {
        $this->authorize('do-everything');

        $this->initializeGit();

        $xmlfiles = [];
        foreach (Storage::files() as $file) {
            if (preg_match('/\.xml/', $file)) {
                $xmlfiles[] = $file;
            }
        }

        $entities = Entity::select('file')->get()->pluck('file')->toArray();

        $refreshed = 0;
        foreach ($xmlfiles as $xmlfile) {
            if (! in_array($xmlfile, $entities)) {
                continue;
            }

            $metadata = Storage::get($xmlfile);
            $refreshed_entity = json_decode($this->parseMetadata($metadata), true);

            if ($refreshed_entity['type'] === 'sp') {
                unset($refreshed_entity['rs']);
            }

            $entity = Entity::whereFile($xmlfile)->first();
            $entity->update($refreshed_entity);

            $edugain = Storage::get(config('git.edugain_tag'));
            $pattern = preg_quote($refreshed_entity['entityid'], '/');
            $pattern = "/^$pattern\$/m";

            if (preg_match_all($pattern, $edugain)) {
                $entity->update(['edugain' => true]);
            } else {
                $entity->update(['edugain' => false]);
            }

            if ($entity->type->value === 'idp') {
                $hfd = Storage::get(config('git.hfd'));
                $pattern = preg_quote($refreshed_entity['entityid'], '/');
                $pattern = "/^$pattern\$/m";

                if (preg_match_all($pattern, $hfd)) {
                    $entity->update(['hfd' => true]);
                } else {
                    $entity->update(['hfd' => false]);
                }
            }

            if ($entity->type->value === 'sp') {
                $rs = Storage::get(config('git.ec_rs'));
                $pattern = preg_quote($refreshed_entity['entityid'], '/');
                $pattern = "/^$pattern\$/m";

                if (preg_match_all($pattern, $rs)) {
                    $entity->update(['rs' => true]);
                } else {
                    $entity->update(['rs' => false]);
                }
            }

            if ($entity->wasChanged()) {
                $refreshed++;
            }
        }

        return redirect('entities')
            ->with('status', trans_choice('entities.refreshed', $refreshed));
    }
}
