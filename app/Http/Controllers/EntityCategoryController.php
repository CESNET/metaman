<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Entity;

class EntityCategoryController extends Controller
{
    public function store(Entity $entity)
    {
        $this->authorize('do-everything');

        if (empty(request('category'))) {
            return redirect()
                ->back()
                ->with('status', __('categories.no_category_selected'))
                ->with('color', 'red');
        }

        $category = Category::findOrFail(request('category'));
        $entity->category()->associate($category);
        $entity->save();

        // TODO work with category (not  ready)
        /*                Bus::chain([
                            new GitDeleteFromCategory($old_category, $entity, Auth::user()),
                            new GitAddToCategory($category, $entity, Auth::user()),
                            function () use ($entity, $category) {
                                $admins = User::activeAdmins()->select('id', 'email')->get();
                                Notification::send($admins, new IdpCategoryChanged($entity, $category));
                            },
                        ])->dispatch();*/

        if (! $entity->wasChanged()) {
            return redirect()
                ->back();
        }

        return redirect()
            ->route('entities.show', $entity)
            ->with('status', __('entities.category_updated'));

    }
}
