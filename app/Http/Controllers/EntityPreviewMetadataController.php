<?php

namespace App\Http\Controllers;

use App\Models\Entity;

class EntityPreviewMetadataController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Entity $entity)
    {
        $this->authorize('view', $entity);

        return response($entity->metadata, 200)
            ->header('Content-Type', 'application/xml');
    }
}
