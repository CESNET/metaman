<?php

namespace App\Http\Controllers;

use App\Facades\HfdTag;
use App\Models\Entity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class EntityHfdController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function update(Entity $entity): RedirectResponse
    {
        $this->authorize('do-everything');

        if ($entity->type->value !== 'idp') {
            return redirect()
                ->back()
                ->with('status', __('categories.hfd_controlled_for_idps_only'));
        }

        $entity = DB::transaction(function () use ($entity) {
            $entity->hfd = ! $entity->hfd;
            $xml_document = HfdTag::update($entity);
            if ($xml_document) {
                $entity->xml_file = $xml_document;
                $entity->update();
            }

            return $entity;
        });

        $status = $entity->hfd ? 'hfd' : 'no_hfd';
        $color = $entity->hfd ? 'red' : 'green';

        return redirect()
            ->route('entities.show', $entity)
            ->with('status', __("entities.$status"))
            ->with('color', $color);
    }
}
