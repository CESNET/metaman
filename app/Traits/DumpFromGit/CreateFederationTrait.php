<?php
namespace App\Traits\DumpFromGit;

use App\Models\Federation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait CreateFederationTrait
{
    public function createFederations(): void
    {
        $cfgfiles = [];
        foreach (Storage::files() as $file) {
            if (preg_match('/^' . config('git.edugain_cfg') . '$/', $file)) {
                continue;
            }

            if (preg_match('/\.cfg$/', $file)) {
                $cfgfiles[] = $file;
            }
        }
        $federations = Federation::select('cfgfile')->get()->pluck('cfgfile')->toArray();

        $unknown = [];
        foreach ($cfgfiles as $cfgfile) {
            if (in_array($cfgfile, $federations)) {
                continue;
            }

            $content = Storage::get($cfgfile);
            preg_match('/\[(.*)\]/', $content, $xml_id);
            preg_match('/filters\s*=\s*(.*)/', $content, $filters);
            preg_match('/name\s*=\s*(.*)/', $content, $xml_name);

            $unknown[$cfgfile]['cfgfile'] = $cfgfile;
            $unknown[$cfgfile]['xml_id'] = $xml_id[1];
            $unknown[$cfgfile]['filters'] = $filters[1];
            $unknown[$cfgfile]['xml_name'] = $xml_name[1];
        }

        foreach ($unknown as $fed) {
            DB::transaction(function () use ($fed) {
                $federation = Federation::create([
                    'name' => $fed['xml_id'],
                    'description' => $fed['xml_id'],
                    'tagfile' => preg_replace('/\.cfg$/', '.tag', $fed["cfgfile"]),
                    'cfgfile' => $fed["cfgfile"],
                    'xml_id' => $fed["xml_id"],
                    'xml_name' => $fed["xml_name"],
                    'filters' => $fed["filters"],
                    'explanation' => 'Imported from Git repository.',
                ]);

                $federation->approved = true;
                $federation->update();
            });
        }
    }

}
