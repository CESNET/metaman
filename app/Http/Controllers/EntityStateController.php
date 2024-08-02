<?php

namespace App\Http\Controllers;

use App\Models\Entity;

class EntityStateController extends Controller
{
    public function state(Entity $entity)
    {
        $this->authorize('delete', $entity);

        $entity->trashed() ? $entity->restore() : $entity->delete();

        $state = $entity->trashed() ? 'deleted' : 'restored';
        $color = $entity->trashed() ? 'red' : 'green';

        $locale = app()->getLocale();

        return redirect()
            ->route('entities.show', $entity)
            ->with('status', __("entities.$state", ['name' => $entity->{"name_$locale"} ?? $entity->entityid]))
            ->with('color', $color);

    }
}
