<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Support\Facades\DB;

class EntityEduGainController extends Controller
{
    public function update(Entity $entity)
    {
        $this->authorize('update', $entity);

        $entity = DB::transaction(function () use ($entity) {
            $entity->edugain = ! $entity->edugain;
            $entity->update();

            return $entity;
        });

        $status = $entity->edugain ? 'edugain' : 'no_edugain';
        $color = $entity->edugain ? 'green' : 'red';

        return redirect()
            ->back()
            ->with('status', __("entities.$status"))
            ->with('color', $color);
    }
}
