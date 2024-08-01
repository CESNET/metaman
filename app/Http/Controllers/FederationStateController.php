<?php

namespace App\Http\Controllers;

use App\Models\Federation;
use App\Notifications\FederationStateChanged;
use App\Services\NotificationService;

class FederationStateController extends Controller
{
    public function state(Federation $federation)
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
