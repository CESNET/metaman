<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class EntityMetadataController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    private function validateEntity(Entity $entity): ?RedirectResponse
    {
        $this->authorize('view', $entity);

        if (! $entity->approved) {
            return to_route('entities.show', $entity)
                ->with('status', __('entities.not_yet_approved'))
                ->with('color', 'red');
        }

        if (is_null(optional($entity->federations->first())->name)) {
            return to_route('entities.show', $entity)
                ->with('status', __('entities.without_federation'))
                ->with('color', 'red');
        }

        return null;
    }

    /**
     * @throws AuthorizationException
     */
    public function store(Entity $entity): Application|Response|RedirectResponse|ResponseFactory
    {
        if ($redirectResponse = $this->validateEntity($entity)) {
            return $redirectResponse;
        }

        return response($entity->xml_file)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="'.$entity->file.'"');
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Entity $entity): Application|Response|RedirectResponse|ResponseFactory
    {

        if ($redirectResponse = $this->validateEntity($entity)) {
            return $redirectResponse;
        }

        return response($entity->xml_file)
            ->header('Content-Type', 'application/xml');
    }
}
