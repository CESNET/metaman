<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class FakeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (App::environment(['local', 'testing'])) {
            $user = User::findOrFail($request->id);

            Auth::login($user);
            Session::regenerate();

            return redirect()->intended('/');
        }
    }

    public function destroy(): RedirectResponse
    {
        if (App::environment(['local', 'testing'])) {
            Auth::logout();
            Session::flush();

            return redirect('/');
        }
    }
}
