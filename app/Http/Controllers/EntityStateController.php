<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;

class EntityStateController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function update(Entity $entity): RedirectResponse
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
