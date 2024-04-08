<?php

namespace App\View\Components\Forms\Element;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Textarea extends Component
{
    /**
     * Create a new component instance.
     */
    public $content;
    public $err;
    public function __construct($err,$content = null)
    {
        if(is_null($content))
        {
            $this->content = $err;
        }
        else
        {
            $this->content = $content;
        }
        $this->err = $err;

    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.forms.element.textarea');
    }
}
