<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategory;
use App\Http\Requests\UpdateCategory;
use App\Jobs\GitAddCategory;
use App\Jobs\GitDeleteCategory;
use App\Jobs\GitUpdateCategory;
use App\Models\Category;
use App\Models\User;
use App\Notifications\CategoryCreated;
use App\Notifications\CategoryDeleted;
use App\Notifications\CategoryUpdated;
use App\Traits\GitTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    use GitTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('do-everything');

        return view('categories.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCategory $request)
    {
        $this->authorize('do-everything');

        $validated = $request->validated();

        $category = Category::create(array_merge(
            $validated,
            ['tagfile' => generateFederationID($validated['name']).'.tag'],
        ));

        GitAddCategory::dispatch($category, Auth::user());
        Notification::send(User::activeAdmins()->select('id', 'email')->get(), new CategoryCreated($category));

        return redirect('categories')
            ->with('status', __('categories.added', ['name' => $category->name]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Category  $category
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
     * @param  \App\Models\Category  $category
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
     * @param  \App\Models\Category  $category
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

        GitUpdateCategory::dispatch($old_category, $category, Auth::user());
        Notification::send(User::activeAdmins()->select('id', 'email')->get(), new CategoryUpdated($category));

        return redirect()
            ->route('categories.show', $category)
            ->with('status', __('categories.updated', ['name' => $old_category]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
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

        GitDeleteCategory::dispatch($name, Auth::user());
        Notification::send(User::activeAdmins()->select('id', 'email')->get(), new CategoryDeleted($name));

        return redirect('categories')
            ->with('status', __('categories.deleted', ['name' => $name]));
    }
}
