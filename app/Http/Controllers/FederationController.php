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
use App\Traits\GitTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class FederationController extends Controller
{
    use GitTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', Federation::class);

        return view('federations.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Federation::class);

        return view('federations.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFederation $request)
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
     * @return \Illuminate\Http\Response
     */
    public function show(Federation $federation)
    {
        $this->authorize('view', $federation);

        return view('federations.show', [
            'federation' => $federation,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Federation $federation)
    {
        $this->authorize('update', $federation);

        return view('federations.edit', [
            'federation' => $federation,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateFederation $request, Federation $federation)
    {
        $this->authorize('update', $federation);

        $validated = $request->validated();

        $id = $federation->name;
        if (isset($validated['name'])) {
            $id = generateFederationID($validated['name']);
        }
        $additionalFilters = $request->input('sp_and_ip_feed', 0);
        $filters = $id;

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
        //  GitUpdateFederation::dispatch($federation, Auth::user());

        return redirect()
            ->route('federations.show', $federation)
            ->with('status', __('federations.updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Federation $federation)
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
