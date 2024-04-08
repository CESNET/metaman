<?php

namespace App\View\Components\Forms\Element;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class input extends Component
{
    /**
     * Create a new component instance.
     */

    public $err;
    public function __construct($err)
    {
        $this->err = $err;
    }
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.forms.element.input');
    }
}
