<?php

namespace App\View\Components\Forms\Section;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormBodySection extends Component
{
    /**
     * Create a new component instance.
     */
    public $name;
    public $label;
    public $err;
    public function __construct($name,$label,$err = null)
    {

        if(is_null($err))
        {
            $this->err = $label;
        }
        else
        {
            $this->err = $err;
        }
        $this->name = $name;
        $this->label = $label;



    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.forms.section.form-body-section');
    }
}
