<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function index(): Factory|Application|View
    {
        $this->authorize('do-everything');

        return view('categories.index');
    }

    /**
     * Display the specified resource.
     *
     * @throws AuthorizationException
     */
    public function show(Category $category): Factory|View|Application
    {
        $this->authorize('do-everything');

        return view('categories.show', compact('category'));
    }
}
