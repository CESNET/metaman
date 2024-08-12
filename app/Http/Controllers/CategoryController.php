<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCategory;
use App\Models\Category;
use App\Models\User;
use App\Notifications\CategoryDeleted;
use App\Notifications\CategoryUpdated;
use App\Traits\GitTrait;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    use GitTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('do-everything');

        return view('categories.index');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        $this->authorize('do-everything');

        return view('categories.show', [
            'category' => $category,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        $this->authorize('do-everything');

        return view('categories.edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCategory $request, Category $category)
    {
        $this->authorize('do-everything');

        $old_category = $category->tagfile;
        $category->update($request->validated());

        if (! $category->wasChanged()) {
            return redirect()
                ->route('categories.show', $category);
        }

        Notification::send(User::activeAdmins()->select('id', 'email')->get(), new CategoryUpdated($category));

        return redirect()
            ->route('categories.show', $category)
            ->with('status', __('categories.updated', ['name' => $old_category]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        $this->authorize('do-everything');

        if ($category->entities->count() !== 0) {
            return redirect()
                ->route('categories.show', $category)
                ->with('status', __('categories.delete_empty'))
                ->with('color', 'red');
        }

        $name = $category->tagfile;
        $category->delete();

        Notification::send(User::activeAdmins()->select('id', 'email')->get(), new CategoryDeleted($name));

        return redirect('categories')
            ->with('status', __('categories.deleted', ['name' => $name]));
    }
}
