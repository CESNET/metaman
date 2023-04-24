<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignOrganization;
use App\Ldap\CesnetOrganization;
use App\Ldap\EduidczOrganization;
use App\Models\Entity;
use App\Traits\ValidatorTrait;

class EntityOrganizationController extends Controller
{
    use ValidatorTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function update(Entity $entity, AssignOrganization $request)
    {
        $this->authorize('do-everything');

        abort_if($entity->type->value !== 'idp', 500);

        try {
            $organization = CesnetOrganization::select('dn')->whereDc($request->organization)->firstOrFail();
        } catch (\LdapRecord\Models\ModelNotFoundException) {
            abort(500);
        }

        $parsed_metadata = json_decode($this->parseMetadata($entity->metadata), true);

        $eduidczOrganization = EduidczOrganization::create([
            'dc' => now()->timestamp,
            'oPointer' => $organization->getDn(),
            'entityIDofIdP' => $entity->entityid,
            'eduIDczScope' => $parsed_metadata['scope'],
        ]);

        abort_if(is_null($eduidczOrganization), 500);

        return to_route('entities.show', $entity)
            ->with('status', __('entities.organization_assigned'));
    }
}
