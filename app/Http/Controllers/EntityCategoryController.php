<?php

namespace App\Http\Controllers;

use App\Facades\CategoryTag;
use App\Models\Category;
use App\Models\Entity;
use App\Models\User;
use App\Notifications\IdpCategoryChanged;
use Illuminate\Support\Facades\Notification;

class EntityCategoryController extends Controller
{
    public function update(Entity $entity)
    {
        $this->authorize('do-everything');

        if (empty(request('category'))) {
            return redirect()
                ->back()
                ->with('status', __('categories.no_category_selected'))
                ->with('color', 'red');
        }

        $category = Category::findOrFail(request('category'));

        $xml_file = CategoryTag::delete($entity);
        if ($xml_file) {
            $entity->xml_file = $xml_file;
        }
        $entity->category()->associate($category);
        $entity->xml_file = CategoryTag::create($entity);
        $entity->save();
        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::send($admins, new IdpCategoryChanged($entity, $category));

        if (! $entity->wasChanged()) {
            return redirect()
                ->back();
        }

        return redirect()
            ->route('entities.show', $entity)
            ->with('status', __('entities.category_updated'));

    }
}
