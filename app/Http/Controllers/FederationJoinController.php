<?php

namespace App\Http\Controllers;

use App\Models\Federation;
use App\Models\Membership;

class FederationJoinController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Federation $federation)
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
