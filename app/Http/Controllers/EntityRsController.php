<?php

namespace App\Http\Controllers;

use App\Facades\RsTag;
use App\Mail\AskRs;
use App\Models\Entity;
use App\Notifications\EntityAddedToRs;
use App\Notifications\EntityDeletedFromRs;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EntityRsController extends Controller
{
    public function store(Entity $entity)
    {
        $this->authorize('update', $entity);

        abort_unless($entity->federations()->where('xml_name', config('git.rs_federation'))->count(), 403, __('entities.rs_only_for_eduidcz_members'));

        Mail::to(config('mail.admin.address'))
            ->send(new AskRs($entity));

        return redirect()
            ->back()
            ->with('status', __('entities.rs_asked'));
    }

    public function rsState(Entity $entity)
    {
        $this->authorize('do-everything');

        if ($entity->type->value !== 'sp') {
            return redirect()
                ->back()
                ->with('status', __('categories.rs_controlled_for_sps_only'));
        }

        $entity = DB::transaction(function () use ($entity) {
            $entity->rs = ! $entity->rs;
            $xml_document = RsTag::update($entity);
            if ($xml_document) {
                $entity->xml_file = $xml_document;
                $entity->update();
            }

            return $entity;
        });

        if ($entity->rs) {
            NotificationService::sendOperatorNotification($entity->operators, new EntityAddedToRs($entity));
        } else {
            NotificationService::sendOperatorNotification($entity->operators, new EntityDeletedFromRs($entity));
        }

        $status = $entity->rs ? 'rs' : 'no_rs';
        $color = $entity->rs ? 'green' : 'red';

        return redirect()
            ->back()
            ->with('status', __("entities.$status"))
            ->with('color', $color);
    }
}
