<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinFederation;
use App\Jobs\GitDeleteFromFederation;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\User;
use App\Notifications\EntityDeletedFromFederation;
use App\Traits\GitTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class EntityFederationController extends Controller
{
    use GitTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Entity $entity)
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

    public function store(JoinFederation $request, Entity $entity)
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

    public function destroy(Request $request, Entity $entity)
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
            GitDeleteFromFederation::dispatch($entity, $federation, Auth::user());
            Notification::send($entity->operators, new EntityDeletedFromFederation($entity, $federation));
            Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityDeletedFromFederation($entity, $federation));
        }

        return redirect()
            ->back()
            ->with('status', __('entities.federations_left'));
    }
}
