<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUser;
use App\Models\User;
use App\Notifications\UserCreated;
use App\Notifications\UserRoleChanged;
use App\Notifications\UserStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);

        return view('users.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUser $request)
    {
        $this->authorize('create', User::class);

        $user = User::create($request->validated());

        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::send($admins, new UserCreated($user));

        return redirect('users')
            ->with('status', __('users.added', ['name' => $user->name]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load('federations', 'entities');

        return view('users.show', [
            'user' => $user,
            'emails' => explode(';', $user->emails),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        switch (request('action')) {
            case 'status':

                $this->authorize('do-everything');

                if ($request->user()->is($user)) {
                    return redirect()
                        ->back()
                        ->with('status', __('users.cannot_toggle_your_status'))
                        ->with('color', 'red');
                }

                $user->active = $user->active ? false : true;
                $user->update();

                $status = $user->active ? 'active' : 'inactive';
                $color = $user->active ? 'green' : 'red';

                $admins = User::activeAdmins()->select('id', 'email')->get();
                Notification::send($user, new UserStatusChanged($user));
                Notification::send($admins, new UserStatusChanged($user));

                return redirect()
                    ->back()
                    ->with('status', __("users.$status", ['name' => $user->name]))
                    ->with('color', $color);

                break;

            case 'role':

                $this->authorize('do-everything');

                if ($request->user()->is($user)) {
                    return redirect()
                        ->back()
                        ->with('status', __('users.cannot_toggle_your_role'))
                        ->with('color', 'red');
                }

                $user->admin = $user->admin ? false : true;
                $user->update();

                $role = $user->admin ? 'admined' : 'deadmined';
                $color = $user->admin ? 'indigo' : 'yellow';

                $admins = User::activeAdmins()->select('id', 'email')->get();
                Notification::send($user, new UserRoleChanged($user));
                Notification::send($admins, new UserRoleChanged($user));

                return redirect()
                    ->back()
                    ->with('status', __("users.$role", ['name' => $user->name]))
                    ->with('color', $color);

                break;

            case 'email':

                $this->authorize('update', $user);

                $emails = explode(';', $user->emails);
                if (in_array($request->email, $emails)) {
                    $user->update(['email' => $request->email]);
                }

                if (! $user->wasChanged()) {
                    return back();
                }

                return back()
                    ->with('status', __('users.email_changed'));

                break;

            default:

                return redirect()->route('users.show', $user);
        }
    }
}
