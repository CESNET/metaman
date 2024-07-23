<?php

namespace App\Http\Controllers;

use App\Jobs\GitAddToHfd;
use App\Models\Membership;
use App\Models\User;
use App\Notifications\EntityAddedToHfd;
use App\Notifications\MembershipAccepted;
use App\Notifications\MembershipRejected;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class MembershipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Membership $membership)
    {
        $this->authorize('update', $membership);

        DB::transaction(function () use ($membership) {
            $membership->entity->approved = true;
            $membership->entity->update();

            $membership->approved = true;
            $membership->approved_by = Auth::id();
            $membership->update();
        });

        // TODO chain of update membership chain (functional)
        /*        Bus::chain([
                    new Old_GitAddEntity($membership->entity, Auth::user()),
                    new GitAddToHfd($membership->entity, Auth::user()),
                    new GitAddMembership($membership, Auth::user()),
                    function () use ($membership) {
                        $admins = User::activeAdmins()->select('id', 'email')->get();
                        Notification::send($membership->entity->operators, new MembershipAccepted($membership));
                        Notification::send($admins, new MembershipAccepted($membership));
                        if ($membership->entity->hfd) {
                            Notification::send($membership->entity->operators, new EntityAddedToHfd($membership->entity));
                            Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityAddedToHfd($membership->entity));
                        }
                    },
                ])->dispatch();*/

        return redirect()
            ->back()
            ->with('status', __('federations.membership_accepted', ['entity' => $membership->entity->entityid]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Membership $membership)
    {
        $this->authorize('delete', $membership);

        $entity = $membership->entity->entityid;

        $locale = app()->getLocale();

        $federation = $membership->federation->name;
        $entity = $membership->entity->{"name_$locale"} ?? $membership->entity->entityid;
        $operators = $membership->entity->operators;

        if (! $membership->entity->approved) {
            $membership->entity->forceDelete();
        }

        $membership->delete();

/*        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::send($operators, new MembershipRejected($entity, $federation));
        Notification::send($admins, new MembershipRejected($entity, $federation));*/

        return redirect()
            ->back()
            ->with('status', __('federations.membership_rejected', ['entity' => $entity]))
            ->with('color', 'red');
    }
}
