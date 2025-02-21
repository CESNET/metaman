<?php

namespace App\Livewire;

use App\Models\Federation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class SearchFederations extends Component
{
    #[Url(except: '')]
    public $search = '';

    public function render()
    {
        return view('livewire.search-federations', [
            'federations' => Federation::query()
                ->visibleTo(Auth::user())
                ->search($this->search)
                ->orderByDesc('approved')
                ->orderBy('name')
                ->paginate(),
        ]);
    }
}
