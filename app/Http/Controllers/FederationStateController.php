<?php

namespace App\Http\Controllers;

use App\Models\Federation;
use App\Notifications\FederationStateChanged;
use App\Services\NotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;

class FederationStateController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function update(Federation $federation): RedirectResponse
    {
        $this->authorize('delete', $federation);

        $federation->trashed() ? $federation->restore() : $federation->delete();

        $state = $federation->trashed() ? 'deleted' : 'restored';
        $color = $federation->trashed() ? 'red' : 'green';

        NotificationService::sendModelNotification($federation, new FederationStateChanged($federation));

        return redirect()
            ->route('federations.show', $federation)
            ->with('status', __("federations.$state", ['name' => $federation->name]))
            ->with('color', $color);
    }
}
