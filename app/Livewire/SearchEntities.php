<?php

namespace App\Livewire;

use App\Models\Entity;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SearchEntities extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public $search = '';

    public $locale;

    public function mount()
    {
        $this->locale = app()->getLocale();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.search-entities', [
            'entities' => Entity::query()
                ->visibleTo(Auth::user())
                ->search($this->search)
                ->orderByDesc('approved')
                ->orderBy("name_{$this->locale}")
                ->paginate(),
        ]);
    }
}
