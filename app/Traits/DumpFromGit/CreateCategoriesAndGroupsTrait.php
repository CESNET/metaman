<?php
namespace App\Traits\DumpFromGit;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait CreateCategoriesAndGroupsTrait
{
    public function createCategoriesAndGroups(): void
    {
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
        $names =[];
        $descriptions = [];
        $categories_conf = config('categories');
        foreach ($unknown as $item) {
            $names[$item] = preg_replace('/\.tag/', '', $item);
            $descriptions[$item] = preg_replace('/\.tag/', '', $item);

            if(!empty( $categories_conf[$names[$item]] ))
            {
                DB::transaction(function () use ($categories_conf, $item, $names, $descriptions) {
                    Category::create([
                        'name' => $names[$item],
                        'description' => $descriptions[$item],
                        'tagfile' => $item,
                        'xml_value'=> $categories_conf[$names[$item]]
                    ]);
                });
            }
            else
            {
                DB::transaction(function () use ($item, $names, $descriptions) {
                    Group::create([
                        'name' => $names[$item],
                        'description' => $descriptions[$item],
                        'tagfile' => $item,
                    ]);
                });
            }
        }
    }


    private function updateCategories(): void
    {
        foreach (Category::select('id', 'tagfile')->get() as $category) {
            $members = explode("\n", trim(Storage::get($category->tagfile)));

            if (! count($members)) {
                continue;
            }

            foreach ($members as $entityid) {
                Entity::whereEntityid($entityid)->first()->category()->associate($category)->save();
            }
        }
    }

    private function updateGroups(): void
    {
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
    }

    public function  updateGroupsAndCategories(): void
    {
        $this->updateGroups();
        $this->updateCategories();
    }
}
