<?php

namespace App\Livewire;

use App\Models\Group;
use Livewire\Attributes\Url;
use Livewire\Component;

class SearchGroups extends Component
{
    #[Url(except: '')]
    public string $search = '';

    public function render()
    {
        return view('livewire.search-groups', [
            'groups' => Group::query()
                ->search($this->search)
                ->orderBy('name')
                ->withCount('entities')
                ->paginate(),
        ]);
    }
}
