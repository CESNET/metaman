<?php

namespace App\Http\Controllers;

use App\Facades\CategoryTag;
use App\Http\Requests\UpdateEntityCategory;
use App\Models\Category;
use App\Models\Entity;
use App\Models\User;
use App\Notifications\IdpCategoryChanged;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

class EntityCategoryController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function update(UpdateEntityCategory $request, Entity $entity): RedirectResponse
    {
        $this->authorize('do-everything');

        $validated = $request->validated();
        $category = Category::findOrFail($validated['category']);

        $entity->xml_file = CategoryTag::delete($entity);
        $entity->category()->associate($category);
        $entity->xml_file = CategoryTag::create($entity);
        $entity->save();

        if (! $entity->wasChanged('category_id')) {
            return redirect()->back();
        }

        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::send($admins, new IdpCategoryChanged($entity, $category));

        return redirect()
            ->route('entities.show', $entity)
            ->with('status', __('entities.category_updated'));
    }
}
