<?php

namespace App\Http\Controllers;

use App\Facades\CategoryTag;
use App\Models\Category;
use App\Models\Entity;
use App\Models\User;
use App\Notifications\IdpCategoryChanged;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class EntityCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function index(Entity $entity): Factory|Application|View
    {
        $this->authorize('do-everything');
        $categories = $entity->category() ? $entity->category()->get() : collect();
        $joinable = Category::orderBy('name')
            ->whereNotIn('id', $categories->pluck('id'))
            ->get();

        return view('entities.categories', [
            'entity' => $entity,
            'categories' => $categories,
            'joinable' => $joinable,
        ]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws AuthorizationException
     */
    public function store(Request $request, Entity $entity): RedirectResponse
    {
        $this->authorize('do-everything');

        if (empty(request('category'))) {
            return back()
                ->with('status', __('entities.join_empty_category'))
                ->with('color', 'red');
        }
        $entity->category()->associate(request('category'));
        $entity->save();

        return redirect()
            ->back()
            ->with('status', __('entities.join_category', [
                'name' => Category::findOrFail($request->input('category'))->name,
            ]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws AuthorizationException
     */
    public function destroy(Request $request, Entity $entity): RedirectResponse
    {
        $this->authorize('do-everything');
        $entity->category()->dissociate();
        $entity->save();

        return redirect()
            ->back()
            ->with('status', __('entities.leave_category'));

    }

    /**
     * @throws AuthorizationException
     */
    public function update(Entity $entity): RedirectResponse
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
