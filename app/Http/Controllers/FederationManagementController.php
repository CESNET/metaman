<?php

namespace App\Http\Controllers;

use App\Models\Federation;
use App\Traits\GitTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FederationManagementController extends Controller
{
    use GitTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('do-everything');

        $this->initializeGit();

        $cfgfiles = [];
        foreach (Storage::files() as $file) {
            if (preg_match('/^'.config('git.edugain_cfg').'$/', $file)) {
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
            preg_match('/name\s*=\s*(.*)/', $content, $name);

            $unknown[$cfgfile]['cfgfile'] = $cfgfile;
            $unknown[$cfgfile]['xml_id'] = $xml_id[1];
            $unknown[$cfgfile]['filters'] = $filters[1];
            $unknown[$cfgfile]['name'] = $name[1];
        }

        if (empty($unknown)) {
            return redirect()
                ->route('federations.index')
                ->with('status', __('federations.nothing_to_import'));
        }

        return view('federations.import', [
            'federations' => $unknown,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('do-everything');

        if (empty(request('federations'))) {
            return back()
                ->with('status', __('federations.empty_import'))
                ->with('color', 'red');
        }

        $imported = 0;
        $names = request('names');
        $descriptions = request('descriptions');
        foreach (request('federations') as $cfgfile) {
            $content = Storage::get($cfgfile);
            preg_match('/\[(.*)\]/', $content, $xml_id);
            preg_match('/filters\s*=\s*(.*)/', $content, $filters);
            preg_match('/name\s*=\s*(.*)/', $content, $xml_name);

            if (empty($names[$cfgfile])) {
                $names[$cfgfile] = preg_replace('/\.cfg$/', '', $cfgfile);
            }

            if (empty($descriptions[$cfgfile])) {
                $descriptions[$cfgfile] = preg_replace('/\.cfg$/', '', $cfgfile);
            }

            DB::transaction(function () use ($cfgfile, $names, $descriptions, $xml_id, $xml_name, $filters) {
                $federation = Federation::create([
                    'name' => $names[$cfgfile],
                    'description' => $descriptions[$cfgfile],
                    'tagfile' => preg_replace('/\.cfg$/', '.tag', $cfgfile),
                    'cfgfile' => $cfgfile,
                    'xml_id' => $xml_id[1],
                    'xml_name' => $xml_name[1],
                    'filters' => $filters[1],
                    'explanation' => 'Imported from Git repository.',
                ]);

                $federation->approved = true;
                $federation->update();
            });

            // FIXME: Notification

            $imported++;
        }

        return redirect('federations')
            ->with('status', trans_choice('federations.imported', $imported));
    }

    public function update()
    {
        $this->authorize('do-everything');

        $this->initializeGit();

        $cfgfiles = [];
        foreach (Storage::files() as $file) {
            if (preg_match('/\.cfg/', $file)) {
                $cfgfiles[] = $file;
            }
        }

        $federations = Federation::select('cfgfile')->get()->pluck('cfgfile')->toArray();

        $refreshed = 0;
        foreach ($cfgfiles as $cfgfile) {
            if (! in_array($cfgfile, $federations)) {
                continue;
            }

            $content = Storage::get($cfgfile);
            preg_match('/\[(.*)\]/', $content, $xml_id);
            preg_match('/filters\s*=\s*(.*)/', $content, $filters);
            preg_match('/name\s*=\s*(.*)/', $content, $xml_name);

            $federation = Federation::whereCfgfile($cfgfile)->first();
            $federation->update([
                'xml_id' => $xml_id[1],
                'xml_name' => $xml_name[1],
                'filters' => $filters[1],
            ]);

            if ($federation->wasChanged()) {
                $refreshed++;
            }
        }

        return redirect('federations')
            ->with('status', trans_choice('federations.refreshed', $refreshed));
    }
}
