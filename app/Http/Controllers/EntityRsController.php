<?php

namespace App\Http\Controllers;

use App\Mail\AskRs;
use App\Models\Entity;
use Illuminate\Support\Facades\Mail;

class EntityRsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

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
}
