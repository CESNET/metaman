<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinFederation;
use App\Jobs\FolderDeleteMembership;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\User;
use App\Notifications\EntityDeletedFromFederation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class EntityFederationController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(Entity $entity): Factory|Application|View
    {
        $this->authorize('view', $entity);

        $federations = $entity->federations;
        $requested = $entity->federationsRequested;
        $collection = $federations->concat($requested);
        $joinable = Federation::orderBy('name')
            ->whereNotIn('id', $collection->pluck('id'))
            ->get();

        return view('entities.federations', [
            'entity' => $entity,
            'federations' => $federations,
            'joinable' => $joinable,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(JoinFederation $request, Entity $entity): RedirectResponse
    {
        $this->authorize('update', $entity);

        if (empty(request('federation'))) {
            return back()
                ->with('status', __('entities.join_empty_federations'))
                ->with('color', 'red');
        }

        $entity
            ->federations()
            ->attach($request->input('federation'), [
                'requested_by' => Auth::id(),
                'explanation' => $request->input('explanation'),
            ]);

        return redirect()
            ->back()
            ->with('status', __('entities.join_requested', [
                'name' => Federation::findOrFail($request->input('federation'))->name,
            ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Request $request, Entity $entity): RedirectResponse
    {
        $this->authorize('update', $entity);

        if (empty(request('federations'))) {
            return back()
                ->with('status', __('entities.leave_empty_federations'))
                ->with('color', 'red');
        }

        $entity
            ->federations()
            ->detach($request->input('federations'));

        foreach (request('federations') as $f) {
            $federation = Federation::find($f);

            FolderDeleteMembership::dispatch($entity, $federation);

            Notification::send($entity->operators, new EntityDeletedFromFederation($entity, $federation));
            Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityDeletedFromFederation($entity, $federation));
        }

        return redirect()
            ->back()
            ->with('status', __('entities.federations_left'));
    }
}
