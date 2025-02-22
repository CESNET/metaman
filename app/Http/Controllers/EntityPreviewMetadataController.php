<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Response;

class EntityPreviewMetadataController extends Controller
{
    /**
     * @return ResponseFactory|Application|Response|object
     *
     * @throws AuthorizationException
     */
    public function show(Entity $entity): Application|Response|ResponseFactory
    {
        $this->authorize('view', $entity);

        return response($entity->metadata, 200)
            ->header('Content-Type', 'application/xml');
    }
}
