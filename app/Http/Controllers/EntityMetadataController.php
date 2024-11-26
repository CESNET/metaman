<?php

namespace App\Http\Controllers;

use App\Models\Entity;

class EntityMetadataController extends Controller
{
    public function store(Entity $entity)
    {
        $this->authorize('view', $entity);

        if (! $entity->approved) {
            return to_route('entities.show', $entity)
                ->with('status', __('entities.not_yet_approved'))
                ->with('color', 'red');
        }

        $folderName = optional($entity->federations->first())->name;
        if (is_null($folderName)) {
            return to_route('entities.show', $entity)
                ->with('status', __('entities.without_federation'))
                ->with('color', 'red');
        }

        return response($entity->xml_file)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="'.$entity->file.'"');
    }

    public function show(Entity $entity)
    {
        $this->authorize('view', $entity);

        if (! $entity->approved) {
            return to_route('entities.show', $entity)
                ->with('status', __('entities.not_yet_approved'))
                ->with('color', 'red');
        }

        $folderName = optional($entity->federations->first())->name;
        if (is_null($folderName)) {
            return to_route('entities.show', $entity)
                ->with('status', __('entities.without_federation'))
                ->with('color', 'red');
        }

        return response($entity->xml_file)
            ->header('Content-Type', 'application/xml');
    }
}
