<?php

namespace App\Livewire;

use App\Models\Group;
use Livewire\Component;

class SearchGroups extends Component
{
    public $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function render()
    {
        $groups = Group::query()
            ->search($this->search)
            ->orderBy('name')
            ->withCount('entities')
            ->paginate();

        return view('livewire.search-groups', [
            'groups' => $groups,
        ]);
    }
}
