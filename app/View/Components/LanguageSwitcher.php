<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LanguageSwitcher extends Component
{

    public $switchTo;
    public $linkUrl;
    public $linkTitle;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->switchTo = app() -> getLocale()  === 'cs' ? 'en' : 'cs';
        $this->linkUrl = "/language/{$this->switchTo}";
        $this->linkTitle = app()->getLocale() === 'cs' ? __('Switch to English') : __('Přepnout do češtiny');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.language-switcher');
    }
}
