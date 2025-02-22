<?php

namespace App\Http\Controllers;

use App\Models\Federation;
use App\Notifications\FederationApproved;
use App\Notifications\FederationRejected;
use App\Services\NotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class FederationApprovalController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @throws AuthorizationException
     */
    public function store(Federation $federation): RedirectResponse
    {
        $this->authorize('do-everything');

        $federation->approved = true;
        $federation->update();

        NotificationService::sendModelNotification($federation, new FederationApproved($federation));

        return redirect()
            ->route('federations.show', $federation)
            ->with('status', __('federations.approved', ['name' => $federation->name]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws AuthorizationException
     */
    public function destroy(Federation $federation): Application|Redirector|RedirectResponse
    {
        $this->authorize('update', $federation);

        $name = $federation->name;
        NotificationService::sendModelNotification($federation, new FederationRejected($name));

        $federation->forceDelete();

        return redirect('federations')
            ->with('status', __('federations.rejected', ['name' => $name]));
    }
}
