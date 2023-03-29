<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Group;
use App\Traits\GitTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryManagementController extends Controller
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

        $categories = Category::select('tagfile')->get()->pluck('tagfile')->toArray();
        $groups = Group::select('tagfile')->get()->pluck('tagfile')->toArray();

        $unknown = [];
        foreach ($tagfiles as $tagfile) {
            if (in_array($tagfile, $categories) || in_array($tagfile, $groups)) {
                continue;
            }

            $cfgfile = preg_replace('/\.tag$/', '.cfg', $tagfile);
            if (! Storage::exists($cfgfile)) {
                $unknown[] = $tagfile;
            }
        }

        if (empty($unknown)) {
            return redirect()
                ->route('categories.index')
                ->with('status', __('categories.nothing_to_import'));
        }

        return view('categories.import', [
            'categories' => $unknown,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('do-everything');

        if (empty(request('categories'))) {
            return back()
                ->with('status', __('categories.empty_import'))
                ->with('color', 'red');
        }

        $imported = 0;
        $names = request('names');
        $descriptions = request('descriptions');
        foreach (request('categories') as $category) {
            if (empty($names[$category])) {
                $names[$category] = preg_replace('/\.tag/', '', $category);
            }

            if (empty($descriptions[$category])) {
                $descriptions[$category] = preg_replace('/\.tag/', '', $category);
            }

            DB::transaction(function () use ($category, $names, $descriptions) {
                Category::create([
                    'name' => $names[$category],
                    'description' => $descriptions[$category],
                    'tagfile' => $category,
                ]);
            });

            $imported++;
        }

        return redirect('categories')
            ->with('status', trans_choice('categories.imported', $imported));
    }

    public function update()
    {
        $this->authorize('do-everything');

        $this->initializeGit();

        if (! Category::count()) {
            return redirect()
                ->route('categories.index')
                ->with('status', __('categories.no_categories'))
                ->with('color', 'red');
        }

        foreach (Category::select('id', 'tagfile')->get() as $category) {
            $members = explode("\n", trim(Storage::get($category->tagfile)));

            if (! count($members)) {
                continue;
            }

            foreach ($members as $entityid) {
                Entity::whereEntityid($entityid)->first()->category()->associate($category)->save();
            }
        }

        return redirect('categories')
            ->with('status', __('categories.refreshed'));
    }
}
