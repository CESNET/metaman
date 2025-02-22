<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFederation;
use App\Http\Requests\UpdateFederation;
use App\Models\Federation;
use App\Models\User;
use App\Notifications\FederationDestroyed;
use App\Notifications\FederationRequested;
use App\Notifications\FederationUpdated;
use App\Services\NotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FederationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function index(): Factory|Application|View
    {
        $this->authorize('viewAny', Federation::class);

        return view('federations.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     */
    public function create(): Factory|Application|View
    {
        $this->authorize('create', Federation::class);

        return view('federations.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws AuthorizationException
     */
    public function store(StoreFederation $request): Application|Redirector|RedirectResponse
    {
        $this->authorize('create', Federation::class);

        $validated = $request->validated();
        $id = generateFederationID($validated['name']);

        $federation = DB::transaction(function () use ($validated, $id) {
            $federation = Federation::create(array_merge($validated, [
                'xml_id' => $id,
                'xml_name' => "urn:mace:cesnet.cz:$id",
                'filters' => $id,
            ]));

            $federation->operators()->attach(Auth::id());

            return $federation;
        });

        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::sendNow($admins, new FederationRequested($federation));

        return redirect('federations')
            ->with('status', __('federations.requested', ['name' => $federation->name]));
    }

    /**
     * Display the specified resource.
     *
     * @throws AuthorizationException
     */
    public function show(Federation $federation): Factory|Application|View
    {
        $this->authorize('view', $federation);

        return view('federations.show', compact('federation'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @throws AuthorizationException
     */
    public function edit(Federation $federation): Factory|Application|View
    {
        $this->authorize('update', $federation);

        return view('federations.edit', compact('federation'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws AuthorizationException
     */
    public function update(UpdateFederation $request, Federation $federation): RedirectResponse
    {
        $this->authorize('update', $federation);

        $validated = $request->validated();

        $id = $federation->name;
        if (isset($validated['name'])) {
            $id = generateFederationID($validated['name']);
        }
        $filters = $id;

        $additionalFilters = $request->input('sp_and_ip_feed', 0);
        if ($additionalFilters) {
            $filters .= ', '.$id.'+idp';
            $filters .= ', '.$id.'+sp';
        }
        $validated['filters'] = $filters;
        $validated['additional_filters'] = $additionalFilters;

        $federation->update($validated);

        if (! $federation->wasChanged()) {
            return redirect()
                ->route('federations.show', $federation);
        }
        NotificationService::sendModelNotification($federation, new FederationUpdated($federation));

        return redirect()
            ->route('federations.show', $federation)
            ->with('status', __('federations.updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws AuthorizationException
     */
    public function destroy(Federation $federation): Application|Redirector|RedirectResponse
    {
        $this->authorize('delete', $federation);

        $name = $federation->name;
        $federation->forceDelete();

        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::send($admins, new FederationDestroyed($name));

        return redirect('federations')
            ->with('status', __('federations.destroyed', ['name' => $federation->name]));
    }
}
