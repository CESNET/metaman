<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LanguageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(string $language): RedirectResponse
    {
        if (isset($language) && in_array($language, config('app.locales'))) {
            app()->setLocale($language);
            session()->put('locale', $language);
        }

        return back();
    }
}
