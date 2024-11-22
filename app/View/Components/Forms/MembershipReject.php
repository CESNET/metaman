<?php

namespace App\View\Components\Forms;

use App\Models\Membership;
use Illuminate\View\Component;

class MembershipReject extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(public Membership $membership)
    {
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.forms.membership-reject');
    }
}
