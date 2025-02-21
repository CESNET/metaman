<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Attributes\Url;
use Livewire\Component;

class SearchCategories extends Component
{
    #[Url(except: '')]
    public $search = '';

    public function render()
    {
        return view('livewire.search-categories', [
            'categories' => Category::query()
                ->search($this->search)
                ->orderBy('name')
                ->withCount('entities')
                ->paginate(),
        ]);
    }
}
