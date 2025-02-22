<?php

namespace App\Http\Controllers;

use App\Models\Federation;
use App\Models\Membership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;

class FederationJoinController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(Federation $federation): Factory|View|Application
    {
        $this->authorize('update', $federation);

        $joins = Membership::with('entity:id,entityid,name_en,name_cs', 'requester:id,name')
            ->where('federation_id', $federation->id)
            ->whereApproved(false)
            ->get();

        return view('federations.requests', [
            'federation' => $federation,
            'joins' => $joins,
        ]);
    }
}
