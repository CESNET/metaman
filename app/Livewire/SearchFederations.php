<?php

namespace App\Livewire;

use App\Models\Federation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SearchFederations extends Component
{
    public $search = '';

    public $queryString = ['search' => ['except' => '']];

    public function render()
    {
        $federations = Federation::query()
            ->visibleTo(Auth::user())
            ->search($this->search)
            ->orderByDesc('approved')
            ->orderBy('name')
            ->paginate();

        return view('livewire.search-federations', [
            'federations' => $federations,
        ]);
    }
}
