<?php

namespace App\Http\Controllers;

use App\Ldap\CesnetOrganization;
use App\Ldap\EduidczOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntityidController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->ico, 400, 'Missing company ID.');

        $organization = CesnetOrganization::findBy('ico', $request->ico);
        abort_unless($organization, 404, 'No organization found.');

        $idp = EduidczOrganization::findBy('opointer', $organization->getDn());
        abort_unless($idp, 404, 'No entityID.');

        return response()->json([
            'entityID' => $idp->getFirstAttribute('entityIDofIdP'),
        ])->setEncodingOptions(JSON_UNESCAPED_SLASHES);
    }
}
