<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Group;
use App\Traits\GitTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GroupManagementController extends Controller
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

        $tagfiles = [];
        foreach (Storage::files() as $file) {
            if (preg_match('/^'.config('git.edugain_tag').'$/', $file)) {
                continue;
            }
            if (preg_match('/^'.config('git.hfd').'$/', $file)) {
                continue;
            }
            if (preg_match('/^'.config('git.ec_rs').'$/', $file)) {
                continue;
            }

            if (preg_match('/\.tag$/', $file)) {
                $tagfiles[] = $file;
            }
        }

        $groups = Group::select('tagfile')->get()->pluck('tagfile')->toArray();
        $categories = Category::select('tagfile')->get()->pluck('tagfile')->toArray();

        $unknown = [];
        foreach ($tagfiles as $tagfile) {
            if (in_array($tagfile, $groups) || in_array($tagfile, $categories)) {
                continue;
            }

            $cfgfile = preg_replace('/\.tag$/', '.cfg', $tagfile);
            if (! Storage::exists($cfgfile)) {
                $unknown[] = $tagfile;
            }
        }

        if (empty($unknown)) {
            return redirect()
                ->route('groups.index')
                ->with('status', __('groups.nothing_to_import'));
        }

        return view('groups.import', [
            'groups' => $unknown,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('do-everything');

        if (empty(request('groups'))) {
            return back()
                ->with('status', __('groups.empty_import'))
                ->with('color', 'red');
        }

        $imported = 0;
        $names = request('names');
        $descriptions = request('descriptions');
        foreach (request('groups') as $group) {
            if (empty($names[$group])) {
                $names[$group] = preg_replace('/\.tag$/', '', $group);
            }

            if (empty($descriptions[$group])) {
                $descriptions[$group] = preg_replace('/\.tag$/', '', $group);
            }

            DB::transaction(function () use ($group, $names, $descriptions) {
                Group::create([
                    'name' => $names[$group],
                    'description' => $descriptions[$group],
                    'tagfile' => $group,
                ]);
            });

            $imported++;
        }

        return redirect('groups')
            ->with('status', trans_choice('groups.imported', $imported));
    }

    public function update()
    {
        $this->authorize('do-everything');

        $this->initializeGit();

        if (! Group::count()) {
            return redirect()
                ->route('groups.index')
                ->with('status', __('groups.no_groups'))
                ->with('color', 'red');
        }

        DB::delete('DELETE FROM entity_group');

        foreach (Group::select('id', 'tagfile')->get() as $group) {
            $members = explode("\n", trim(Storage::get($group->tagfile)));

            if (! count($members)) {
                continue;
            }

            foreach ($members as $entityid) {
                Entity::whereEntityid($entityid)->first()->groups()->syncWithoutDetaching($group);
            }
        }

        return redirect('groups')
            ->with('status', __('groups.refreshed'));
    }
}
