<?php

namespace App\Http\Controllers;

use App\Facades\HfdTag;
use App\Models\Entity;
use Illuminate\Support\Facades\DB;

class EntityHfdController extends Controller
{
    public function update(Entity $entity)
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
