<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MembershipController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @throws AuthorizationException
     */
    public function update(Membership $membership): RedirectResponse
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
     * @throws AuthorizationException
     */
    public function destroy(Membership $membership): RedirectResponse
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
