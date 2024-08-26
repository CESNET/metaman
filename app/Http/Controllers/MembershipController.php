<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $locale = app()->getLocale();
        $entity = $membership->entity->{"name_$locale"} ?? $membership->entity->entityid;

        $membership->delete();

        return redirect()
            ->back()
            ->with('status', __('federations.membership_rejected', ['entity' => $entity]))
            ->with('color', 'red');
    }
}
