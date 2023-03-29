<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Support\Facades\Storage;

class EntityMetadataController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Entity $entity)
    {
        $this->authorize('view', $entity);

        if (! $entity->approved) {
            return to_route('entities.show', $entity)
                ->with('status', __('entities.not_yet_approved'))
                ->with('color', 'red');
        }

        return Storage::download($entity->file);
    }

    public function show(Entity $entity)
    {
        $this->authorize('view', $entity);

        if (! $entity->approved) {
            return to_route('entities.show', $entity)
                ->with('status', __('entities.not_yet_approved'))
                ->with('color', 'red');
        }

        return response()->file(Storage::path($entity->file));
    }
}
