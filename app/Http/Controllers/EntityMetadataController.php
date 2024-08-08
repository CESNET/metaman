<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Support\Facades\Storage;

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

        return Storage::disk(config('storageCfg.name'))->download("{$folderName}/{$entity->file}");
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

        return response()->file(Storage::disk(config('storageCfg.name'))->path("{$folderName}/{$entity->file}"));
    }
}
