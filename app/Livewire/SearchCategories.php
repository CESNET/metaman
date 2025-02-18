<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Component;

class SearchCategories extends Component
{
    public $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function render()
    {
        $categories = Category::query()
            ->search($this->search)
            ->orderBy('name')
            ->withCount('entities')
            ->paginate();

        return view('livewire.search-categories', [
            'categories' => $categories,
        ]);
    }
}
